<?php
namespace Espo\Custom\Hooks\Lead;

use Espo\ORM\Entity;

class AiLeadEnricher
{
    public function beforeSave(Entity $entity): void
    {
        $name = $entity->get('name') ?: 'Client';
        $accountName = $entity->get('accountName') ?: $name;
        $status = $entity->get('status');
        $doNotCall = $entity->get('doNotCall');
        $description = $entity->get('description') ?: '';
        $descLower = strtolower($description);
        $phone = $entity->get('phoneNumber') ?: '';

        // Determine AI Score, Recommendation & Pitch if not already set
        if (!$entity->get('aiScore') || !$entity->get('aiGeneratedPitch')) {
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
            } elseif (strpos($descLower, 'website available: no') !== false || strpos($descLower, 'interior designer') !== false || $entity->get('serviceRequired') === 'website_dev') {
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

            $entity->set('aiScore', $score);
            $entity->set('aiScoreValue', $scoreValue);
            $entity->set('aiRecommendation', $recommendation);
            $entity->set('aiGeneratedPitch', $pitch);
        } else {
            $pitch = $entity->get('aiGeneratedPitch');
        }

        // Generate 1-Click WhatsApp Direct URL
        if ($phone) {
            $digits = preg_replace('/[^0-9]/', '', $phone);
            if (strlen($digits) === 10) {
                $digits = '91' . $digits;
            }
            if ($digits) {
                $encodedText = rawurlencode($pitch);
                $entity->set('whatsappUrl', "https://wa.me/{$digits}?text={$encodedText}");
            }
        }
    }
}
