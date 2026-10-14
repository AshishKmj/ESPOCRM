<?php
namespace Espo\Custom\Classes\ConsoleCommands;

use Espo\Core\Console\Command;
use Espo\Core\Console\Command\Params;
use Espo\Core\Console\IO;
use Espo\ORM\EntityManager;

class ReconcileFollowUps implements Command
{
    public function __construct(
        private EntityManager $entityManager,
    ) {}

    public function run(Params $params, IO $io): void
    {
        $io->writeLine("=====================================================");
        $io->writeLine("🔄 Starting Follow-up History Reconciliation Engine...");
        $io->writeLine("   Preserving authentic records & classifying reminder states");
        $io->writeLine("=====================================================");

        $repository = $this->entityManager->getRDBRepository('Lead');
        $leads = $repository->find();

        $stats = [
            'total' => 0,
            'missing_date' => 0,
            'due_today' => 0,
            'overdue' => 0,
            'scheduled' => 0,
            'cancelled' => 0,
            'completed' => 0
        ];

        $todayStart = date('Y-m-d 00:00:00');
        $todayEnd = date('Y-m-d 23:59:59');
        $now = date('Y-m-d H:i:s');

        foreach ($leads as $lead) {
            $stats['total']++;
            $status = $lead->get('status');
            $description = $lead->get('description') ?: '';
            $descLower = strtolower($description);
            $nextDate = $lead->get('nextFollowUpDate');
            $name = $lead->get('name') ?: 'Lead #' . $lead->getId();

            // Set default followUpPriority if empty
            if (!$lead->get('followUpPriority')) {
                $lead->set('followUpPriority', 'medium');
            }

            // Dead or Converted handling
            if ($status === 'Dead') {
                $lead->set('reminderStatus', 'cancelled');
                if (strpos($descLower, 'abusive') !== false || strpos($descLower, 'hostile') !== false) {
                    $lead->set('lossReason', 'abusive');
                } elseif (strpos($descLower, 'not interested') !== false) {
                    $lead->set('lossReason', 'not_interested');
                }
                $stats['cancelled']++;
                $this->entityManager->saveEntity($lead);
                continue;
            }

            if ($status === 'Converted') {
                $lead->set('reminderStatus', 'completed');
                $stats['completed']++;
                $this->entityManager->saveEntity($lead);
                continue;
            }

            // Existing follow-up count reconstruction from authentic historical data
            if (strpos($descLower, 'original status: follow-up') !== false) {
                if (!$lead->get('followUpCount') || $lead->get('followUpCount') === 0) {
                    $lead->set('followUpCount', 1);
                }
            }

            // Evaluate nextFollowUpDate
            if (empty($nextDate)) {
                $lead->set('reminderStatus', 'missing_date');
                $stats['missing_date']++;
            } else {
                if ($nextDate < $now) {
                    $lead->set('reminderStatus', 'overdue');
                    $stats['overdue']++;
                } elseif ($nextDate >= $todayStart && $nextDate <= $todayEnd) {
                    $lead->set('reminderStatus', 'due_today');
                    $stats['due_today']++;
                } else {
                    $lead->set('reminderStatus', 'scheduled');
                    $stats['scheduled']++;
                }
            }

            $this->entityManager->saveEntity($lead);
        }

        $io->writeLine("\n📊 Reconciliation Complete Summary:");
        $io->writeLine("-----------------------------------------------------");
        $io->writeLine("  Total Leads in Database:      " . $stats['total']);
        $io->writeLine("  ⚠️ Follow-up Date Missing:     " . $stats['missing_date']);
        $io->writeLine("  🚨 Overdue Follow-ups:         " . $stats['overdue']);
        $io->writeLine("  🔔 Due Today Follow-ups:       " . $stats['due_today']);
        $io->writeLine("  📅 Scheduled Future:          " . $stats['scheduled']);
        $io->writeLine("  ⏹️ Cancelled / Dead:          " . $stats['cancelled']);
        $io->writeLine("  ✅ Completed / Converted:      " . $stats['completed']);
        $io->writeLine("=====================================================\n");
    }
}
