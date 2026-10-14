<?php
namespace Espo\Custom\Hooks\Lead;

use Espo\Core\Hook\Hook\BeforeSave;
use Espo\Core\Hook\Hook\AfterSave;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Repository\Option\SaveOptions;

class FollowUpManager implements BeforeSave, AfterSave
{
    private array $pendingFollowUpLogs = [];

    public function __construct(
        private EntityManager $entityManager,
    ) {}

    public function beforeSave(Entity $entity, SaveOptions $options): void
    {
        $status = $entity->get('status');
        $now = date('Y-m-d H:i:s');
        $todayStart = date('Y-m-d 00:00:00');
        $todayEnd = date('Y-m-d 23:59:59');

        // Set default followUpPriority if not set
        if (!$entity->get('followUpPriority')) {
            $entity->set('followUpPriority', 'medium');
        }

        // 1. Handle Dead / Lost status
        if ($status === 'Dead') {
            $entity->set('reminderStatus', 'cancelled');
            return;
        }

        // 2. Handle Converted / Won status
        if ($status === 'Converted') {
            $entity->set('reminderStatus', 'completed');
            if (!$entity->get('convertedAt')) {
                $entity->set('convertedAt', $now);
            }
            return;
        }

        // 3. Track Follow-up Completion when outcome is recorded
        if ($entity->isAttributeChanged('lastFollowUpOutcome') && !empty($entity->get('lastFollowUpOutcome'))) {
            if (!$entity->get('lastFollowUpDate')) {
                $entity->set('lastFollowUpDate', $now);
            }
            $currentCount = (int) $entity->get('followUpCount');
            $entity->set('followUpCount', $currentCount + 1);

            // Queue activity stream log for afterSave
            $id = $entity->getId();
            if ($id) {
                $this->pendingFollowUpLogs[$id] = [
                    'outcome' => $entity->get('lastFollowUpOutcome'),
                    'method' => $entity->get('lastFollowUpMethod') ?: 'call',
                    'notes' => $entity->get('conversationNotes') ?: 'No additional notes provided.',
                    'nextDate' => $entity->get('nextFollowUpDate') ?: 'None scheduled'
                ];
            }
        }

        // 4. Calculate Reminder Status based on Next Follow-up Date
        $nextDate = $entity->get('nextFollowUpDate');

        if (empty($nextDate)) {
            $entity->set('reminderStatus', 'missing_date');
        } else {
            if ($nextDate < $now) {
                $entity->set('reminderStatus', 'overdue');
            } elseif ($nextDate >= $todayStart && $nextDate <= $todayEnd) {
                $entity->set('reminderStatus', 'due_today');
            } else {
                $entity->set('reminderStatus', 'scheduled');
            }
        }
    }

    public function afterSave(Entity $entity, SaveOptions $options): void
    {
        $id = $entity->getId();
        if (isset($this->pendingFollowUpLogs[$id])) {
            $data = $this->pendingFollowUpLogs[$id];
            unset($this->pendingFollowUpLogs[$id]);

            $postContent = "📞 **Follow-up Interaction Recorded**\n\n" .
                "• **Method:** " . ucfirst($data['method']) . "\n" .
                "• **Outcome:** " . ucwords(str_replace('_', ' ', $data['outcome'])) . "\n" .
                "• **Next Follow-up:** " . $data['nextDate'] . "\n" .
                "• **Notes:** " . $data['notes'];

            $userId = $entity->get('assignedUserId') ?: '6acca70ab49fe02b9';

            try {
                $note = $this->entityManager->getEntity('Note');
                $note->set([
                    'type' => 'Post',
                    'parentType' => 'Lead',
                    'parentId' => $id,
                    'post' => $postContent,
                    'createdById' => $userId,
                    'data' => (object) [
                        'source' => 'followUpEngine',
                        'outcome' => $data['outcome'],
                        'method' => $data['method'],
                        'nextDate' => $data['nextDate']
                    ]
                ]);
                $this->entityManager->saveEntity($note);
            } catch (\Throwable $e) {
                $noteId = substr(md5(uniqid(rand(), true)), 0, 17);
                $now = date('Y-m-d H:i:s');
                $pdo = new \PDO('mysql:host=127.0.0.1;dbname=espocrm', 'root', '');
                $stmt = $pdo->prepare("INSERT INTO note (id, deleted, post, type, target_type, parent_id, parent_type, created_by_id, created_at, modified_at) VALUES (?, 0, ?, 'Post', 'all', ?, 'Lead', ?, ?, ?)");
                $stmt->execute([$noteId, $postContent, $id, $userId, $now, $now]);
            }
        }
    }
}
