<?php
namespace Espo\Custom\Classes\ConsoleCommands;

use Espo\Core\Console\Command;
use Espo\Core\Console\Command\Params;
use Espo\Core\Console\IO;
use Espo\ORM\EntityManager;

class AiAnalyzeLeads implements Command
{
    public function __construct(
        private EntityManager $entityManager,
    ) {}

    public function run(Params $params, IO $io): void
    {
        $io->writeLine("🤖 Starting AI Lead Analysis & Pitch Generation for MaaJanki Web Tech...");

        $repository = $this->entityManager->getRDBRepository('Lead');
        $leads = $repository->find();

        $processed = 0;

        foreach ($leads as $lead) {
            $name = $lead->get('name') ?: 'Client';
            $accountName = $lead->get('accountName') ?: $name;
            $status = $lead->get('status');
            $doNotCall = $lead->get('doNotCall');
            $description = $lead->get('description') ?: '';
            $descLower = strtolower($description);

            // AI Decision Engine
            if ($doNotCall || $status === 'Dead' || strpos($descLower, 'not interested') !== false || strpos($descLower, 'do not contact') !== false) {
                $score = 'cold';
                $scoreValue = 5;
                $recommendation = "❌ Negative sentiment detected. Marked as Do Not Contact.";
                $pitch = "";
            } elseif (strpos($descLower, 'google site') !== false || strpos($descLower, 'not open') !== false || strpos($descLower, 'not work') !== false) {
                $score = 'hot';
                $scoreValue = 92;
                $recommendation = "🔥 Prime Redesign Opportunity: Existing site is broken/Google Site. High conversion potential for Next.js revamp.";
                $pitch = "Namaste! Maine {$accountName} ka profile dekha. Aapki current website active nahi dikh rahi hai. MaaJanki Web Tech aapke liye ek ultra-fast, mobile-responsive Next.js 15 website aur Google Maps ranking setup kar sakti hai jisse aapko daily customer inquiries mil sakein.\n\nKya hum aapko hamare banaye hue live projects ka link share karein? - Ashish Kumar (MaaJanki Web Tech, Bagaha/Bettiah - 9006543913)";
            } elseif (strpos($descLower, 'seo') !== false || strpos($descLower, 'optimization') !== false) {
                $score = 'warm';
                $scoreValue = 80;
                $recommendation = "⚡ SEO & Growth Opportunity: Website exists but lacks search ranking. Pitch Local SEO & Google Top 3 ranking audit.";
                $pitch = "Namaste! {$accountName} ki website Google search me top par rank nahi kar rahi hai, jisse aapke competitors ko inquiries ja rahi hain. MaaJanki Web Tech aapko ek FREE Local SEO Audit report bhej sakti hai. Kya hum WhatsApp par report share karein? - Ashish Kumar (MaaJanki Web Tech - 9006543913)";
            } elseif (strpos($descLower, 'website available: no') !== false || strpos($descLower, 'interior designer') !== false) {
                $score = 'hot';
                $scoreValue = 88;
                $recommendation = "🔥 High-Ticket Opportunity: Established business with NO website. Pitch modern portfolio website to showcase past projects.";
                $pitch = "Namaste! Maine {$accountName} ke interior/design works dekhe. Aapke projects bohot premium hain, lekin online portfolio website na hone ki wajah se direct clients reach nahi kar pa rahe.\n\nMaaJanki Web Tech aapke business ke liye ek modern portfolio website bana sakti hai. Humne Bihar & Pan-India me 50+ clients ke liye websites develop ki hain. Kya main aapke saath demo link share karu? - Ashish Kumar (MaaJanki Web Tech - 9006543913)";
            } else {
                $score = 'warm';
                $scoreValue = 65;
                $recommendation = "⚡ Warm Prospect: Send introduction brochure and schedule follow-up call.";
                $pitch = "Namaste! MaaJanki Web Tech ki taraf se message hai. Hum businesses ke liye custom websites, SEO aur digital marketing provide karte hain. Kya aap apne business ko online grow karne me interested hain? - Ashish Kumar (9006543913)";
            }

            $lead->set('aiScore', $score);
            $lead->set('aiScoreValue', $scoreValue);
            $lead->set('aiRecommendation', $recommendation);
            $lead->set('aiGeneratedPitch', $pitch);

            $this->entityManager->saveEntity($lead);
            $processed++;

            $io->writeLine("  -> Processed: {$accountName} | AI Score: {$score} ({$scoreValue})");
        }

        $io->writeLine("✅ Successfully analyzed and enriched {$processed} leads with AI Intelligence!");
    }
}
