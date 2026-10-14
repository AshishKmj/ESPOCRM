<?php
namespace Espo\Custom\Classes\ConsoleCommands;

use Espo\Core\Console\Command;
use Espo\Core\Console\Command\Params;
use Espo\Core\Console\IO;
use Espo\ORM\EntityManager;

class ProcessFollowUpReminders implements Command
{
    public function __construct(
        private EntityManager $entityManager,
    ) {}

    public function run(Params $params, IO $io): void
    {
        $io->writeLine("=====================================================");
        $io->writeLine("⏰ Running Automated Follow-up Reminder Scanner...");
        $io->writeLine("=====================================================");

        $leadRepo = $this->entityManager->getRDBRepository('Lead');
        $notificationRepo = $this->entityManager->getRDBRepository('Notification');

        $leads = $leadRepo->where([
            'status!=' => ['Dead', 'Converted', 'Recycled']
        ])->find();

        $now = date('Y-m-d H:i:s');
        $todayStart = date('Y-m-d 00:00:00');
        $todayEnd = date('Y-m-d 23:59:59');

        $counts = [
            'scanned' => 0,
            'overdue_detected' => 0,
            'due_today_detected' => 0,
            'missing_date' => 0,
            'notifications_sent' => 0
        ];

        foreach ($leads as $lead) {
            $counts['scanned']++;
            $nextDate = $lead->get('nextFollowUpDate');
            $leadId = $lead->getId();
            $leadName = $lead->get('name') ?: 'Lead #' . $leadId;
            $assignedUserId = $lead->get('assignedUserId') ?: '1'; // Default admin if unassigned

            if (empty($nextDate)) {
                if ($lead->get('reminderStatus') !== 'missing_date') {
                    $lead->set('reminderStatus', 'missing_date');
                    $this->entityManager->saveEntity($lead);
                }
                $counts['missing_date']++;
                continue;
            }

            $currentReminderStatus = $lead->get('reminderStatus');
            $targetReminderStatus = $currentReminderStatus;

            if ($nextDate < $now) {
                $targetReminderStatus = 'overdue';
                $counts['overdue_detected']++;
                
                // Track missed count if newly transitioning
                if ($currentReminderStatus !== 'overdue') {
                    $missed = (int) $lead->get('missedFollowUpCount');
                    $lead->set('missedFollowUpCount', $missed + 1);
                }
            } elseif ($nextDate >= $todayStart && $nextDate <= $todayEnd) {
                $targetReminderStatus = 'due_today';
                $counts['due_today_detected']++;
            } else {
                $targetReminderStatus = 'scheduled';
            }

            if ($currentReminderStatus !== $targetReminderStatus) {
                $lead->set('reminderStatus', $targetReminderStatus);
                $this->entityManager->saveEntity($lead);
            }

            // Generate In-App notification for Due Today or Overdue if not already sent today
            if (in_array($targetReminderStatus, ['due_today', 'overdue'])) {
                $existingNotification = $notificationRepo->where([
                    'relatedId' => $leadId,
                    'relatedType' => 'Lead',
                    'userId' => $assignedUserId,
                    'createdAt>=' => $todayStart
                ])->findOne();

                if (!$existingNotification) {
                    $label = ($targetReminderStatus === 'overdue') ? "🚨 OVERDUE FOLLOW-UP" : "🔔 DUE TODAY FOLLOW-UP";
                    $msg = "{$label}: {$leadName} scheduled for {$nextDate}. Please call or message on WhatsApp.";

                    $notification = $this->entityManager->getEntity('Notification');
                    $notification->set([
                        'type' => 'message',
                        'userId' => $assignedUserId,
                        'message' => $msg,
                        'relatedId' => $leadId,
                        'relatedType' => 'Lead',
                        'data' => (object) [
                            'leadName' => $leadName,
                            'reminderStatus' => $targetReminderStatus,
                            'nextDate' => $nextDate
                        ],
                        'read' => false
                    ]);
                    $this->entityManager->saveEntity($notification);
                    $counts['notifications_sent']++;
                }
            }
        }

        $io->writeLine("  Scanned Active Leads:           " . $counts['scanned']);
        $io->writeLine("  🚨 Overdue Follow-ups Flagged:  " . $counts['overdue_detected']);
        $io->writeLine("  🔔 Due Today Follow-ups Flagged:" . $counts['due_today_detected']);
        $io->writeLine("  ⚠️ Missing Follow-up Date:      " . $counts['missing_date']);
        $io->writeLine("  📬 In-App Notifications Dispatched: " . $counts['notifications_sent']);
        $io->writeLine("=====================================================\n");
    }
}
