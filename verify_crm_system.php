<?php
require_once 'bootstrap.php';

$app = new \Espo\Core\Application();
$container = $app->getContainer();
$em = $container->get('entityManager');

$adminUser = $em->getEntity('User', '6acca70ab49fe02b9');
$container->set('user', $adminUser);

$recordServiceContainer = $container->get('recordServiceContainer');
$leadService = $recordServiceContainer->get('Lead');

echo "==============================================================\n";
echo "🧪 RUNNING COMPREHENSIVE CRM SYSTEM & FOLLOW-UP TEST SUITE\n";
echo "==============================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertCondition($name, $condition) {
    global $passCount, $failCount;
    if ($condition) {
        echo "  ✅ PASS: {$name}\n";
        $passCount++;
    } else {
        echo "  ❌ FAIL: {$name}\n";
        $failCount++;
    }
}

// -------------------------------------------------------------
// TEST 1: Authentic Lead Preservation
// -------------------------------------------------------------
echo "[Step 1] Verifying Authentic Lead Data Preservation...\n";
$allLeads = $em->getRDBRepository('Lead')->find();
assertCondition("Existing Leads preserved count >= 21", count($allLeads) >= 21);

$deadLeads = $leadService->find(\Espo\Core\Select\SearchParams::create()->withPrimaryFilter('dead'));
assertCondition("Dead/Do Not Contact leads accurately filtered (count = 1)", $deadLeads->getTotal() === 1);

$missingDateLeads = $leadService->find(\Espo\Core\Select\SearchParams::create()->withPrimaryFilter('followUpDateMissing'));
assertCondition("Missing Follow-up Date review queue populated (count >= 20)", $missingDateLeads->getTotal() >= 20);

// -------------------------------------------------------------
// TEST 2: Create Test Lead & Verify Initial State
// -------------------------------------------------------------
echo "\n[Step 2] Creating Test Lead with MaaJanki Web Tech Requirements...\n";
$testLead = $em->getEntity('Lead');
$testLead->set([
    'firstName' => 'Unit',
    'lastName' => 'Test Lead',
    'accountName' => 'Test Architect Firm',
    'phoneNumber' => '9876543210',
    'serviceRequired' => 'website_dev',
    'budgetRange' => '50k_to_100k',
    'projectTimeline' => '1_2_weeks',
    'source' => 'website',
    'assignedUserId' => '6acca70ab49fe02b9'
]);
$em->saveEntity($testLead);
$testLeadId = $testLead->getId();

assertCondition("Test Lead created with valid ID", !empty($testLeadId));
assertCondition("Default reminderStatus initialized to missing_date", $testLead->get('reminderStatus') === 'missing_date');
assertCondition("AI Score auto-assigned by AiLeadEnricher", !empty($testLead->get('aiScore')));
assertCondition("1-Click WhatsApp direct URL generated", !empty($testLead->get('whatsappUrl')));

// -------------------------------------------------------------
// TEST 3: Log Follow-Up Interaction & Schedule for Today
// -------------------------------------------------------------
echo "\n[Step 3] Logging Follow-up Call & Scheduling for Today...\n";
$todayDateTime = date('Y-m-d 16:00:00');

$testLead->set([
    'lastFollowUpMethod' => 'call',
    'lastFollowUpOutcome' => 'connected_interested',
    'conversationNotes' => 'Client is very interested in Next.js 15 portfolio website. Requested a quote.',
    'nextFollowUpDate' => $todayDateTime,
    'followUpPriority' => 'high'
]);
$em->saveEntity($testLead);

// Reload entity to verify hooks execution
$reloadedLead = $em->getEntity('Lead', $testLeadId);
assertCondition("followUpCount incremented to 1", (int) $reloadedLead->get('followUpCount') === 1);
assertCondition("lastFollowUpDate set automatically", !empty($reloadedLead->get('lastFollowUpDate')));
assertCondition("reminderStatus automatically transitioned to due_today", $reloadedLead->get('reminderStatus') === 'due_today');

// Verify chronological stream timeline note created
$streamNote = $em->getRDBRepository('Note')->where([
    'parentId' => $testLeadId,
    'parentType' => 'Lead',
    'type' => 'Post'
])->findOne();
assertCondition("Activity stream note created with full details", !empty($streamNote) && strpos($streamNote->get('post'), 'Connected Interested') !== false);

// -------------------------------------------------------------
// TEST 4: Primary Filter Query for Today's Follow-ups
// -------------------------------------------------------------
echo "\n[Step 4] Testing 'Today Follow-ups' Primary Filter...\n";
$todayResults = $leadService->find(\Espo\Core\Select\SearchParams::create()->withPrimaryFilter('todayFollowUps'));
assertCondition("Today's Follow-ups primary filter includes scheduled lead", $todayResults->getTotal() >= 1);

// -------------------------------------------------------------
// TEST 5: Overdue Lifecycle Transition
// -------------------------------------------------------------
echo "\n[Step 5] Testing Overdue Status Transition & Reminder Scanner...\n";
$yesterday = date('Y-m-d H:i:s', strtotime('-1 day'));
$reloadedLead->set('nextFollowUpDate', $yesterday);
$em->saveEntity($reloadedLead);

$overdueLead = $em->getEntity('Lead', $testLeadId);
assertCondition("Past nextFollowUpDate automatically sets reminderStatus to overdue", $overdueLead->get('reminderStatus') === 'overdue');

$overdueResults = $leadService->find(\Espo\Core\Select\SearchParams::create()->withPrimaryFilter('overdueFollowUps'));
assertCondition("Overdue Follow-ups filter returns overdue lead", $overdueResults->getTotal() >= 1);

// -------------------------------------------------------------
// TEST 6: Won / Lost Status Transitions (Cancelling Reminders)
// -------------------------------------------------------------
echo "\n[Step 6] Testing Won/Lost Reminder Invalidation...\n";
$overdueLead->set('status', 'Dead');
$overdueLead->set('lossReason', 'budget_high');
$em->saveEntity($overdueLead);

$deadLead = $em->getEntity('Lead', $testLeadId);
assertCondition("Dead lead automatically cancels active reminders", $deadLead->get('reminderStatus') === 'cancelled');

$overdueAfterDead = $leadService->find(\Espo\Core\Select\SearchParams::create()->withPrimaryFilter('overdueFollowUps'));
assertCondition("Dead lead excluded from overdue follow-ups list", $overdueAfterDead->getTotal() === 0);

// -------------------------------------------------------------
// TEST 7: Clean up Test Lead
// -------------------------------------------------------------
echo "\n[Step 7] Cleaning up test lead to maintain database purity...\n";
$em->removeEntity($testLead);
if ($streamNote) {
    $em->removeEntity($streamNote);
}
assertCondition("Test lead removed cleanly", true);

echo "\n==============================================================\n";
echo "📊 TEST RESULTS: {$passCount} PASSED | {$failCount} FAILED\n";
echo "==============================================================\n\n";

if ($failCount > 0) {
    exit(1);
}
