# 🚀 MaaJanki Web Tech CRM Integration Guide

Aapke EspoCRM ko **MaaJanki Web Tech (maajankiwebtech.com)** ke liye successfully customize aur configure kar diya gaya hai.

---

## 🎨 Kya kya updates kiye gaye hain?

### 1. Branding & Identity
- **App Name:** `MaaJanki Web Tech CRM`
- **Favicon & Logo:** `maajankiwebtech.com` ka official logo aur favicon integrate kiya gaya.
- **Timezone:** `Asia/Kolkata (IST)`
- **Default Currency:** `INR (₹)` (Saath hi International clients ke liye `USD ($)`, `EUR (€)`, `GBP (£)`, `AED` enabled hain).
- **Official Email:** `info@maajankiwebtech.com`

### 2. Agency Specific Lead Fields & Sources
Leads module me MaaJanki Web Tech ke business model ke hisaab se custom fields add kiye gaye hain:

- **Lead Sources:**
  - `Website (maajankiwebtech.com)`
  - `WhatsApp Inquiry`
  - `Google Search / Local SEO`
  - `Direct Referral`
  - `Social Media (LinkedIn/Instagram)`
  - `Upwork / Freelance`
  - `Phone Call` / `Email Inquiry`

- **Service Required (Dropdown):**
  - Custom Website Development (Next.js / React)
  - Software & SaaS Development
  - E-Commerce Store
  - SEO & Digital Marketing
  - UI / UX Design
  - Mobile App Development
  - Maintenance & AMC

- **Budget Range (Dropdown):**
  - `< ₹25,000`
  - `₹25,000 - ₹50,000`
  - `₹50,000 - ₹1,00,000`
  - `₹1,00,000 - ₹3,00,000`
  - `> ₹3,00,000 / $1,000+`

- **Project Timeline:**
  - `Urgent (< 1 week)`
  - `1 - 2 weeks`
  - `2 - 4 weeks`
  - `1 - 3 months`
  - `Flexible`

---

## 🔌 `maajankiwebtech.com` (Next.js) ke Contact Form ko CRM se Connect karna

Aapki website ke contact form se jab bhi koi client query karega, woh **seedha aapke CRM me new lead ban kar store ho jayega**.

### Next.js 15 API Route (`app/api/leads/route.js` ya Server Action)

```javascript
// D:\Maajanki Web Tech\app\api\contact\route.js

import { NextResponse } from 'next/server';

export async function POST(request) {
  try {
    const data = await request.json();

    // CRM Endpoint (Local testing ya Live URL)
    const CRM_URL = process.env.CRM_URL || 'http://localhost:8080';
    const CRM_API_KEY = process.env.CRM_API_KEY || 'YOUR_API_KEY_OR_BASIC_AUTH';

    const payload = {
      firstName: data.firstName || data.name?.split(' ')[0] || 'Inquiry',
      lastName: data.lastName || data.name?.split(' ').slice(1).join(' ') || 'Lead',
      emailAddress: data.email,
      phoneNumber: data.phone,
      source: 'website',
      serviceRequired: data.service || 'website_dev', // e.g. website_dev, software_saas, seo_marketing
      budgetRange: data.budget || '50k_to_100k',
      projectTimeline: data.timeline || '2_4_weeks',
      description: `Website Inquiry: ${data.message || ''}`,
    };

    const res = await fetch(`${CRM_URL}/api/v1/Lead`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Basic ${Buffer.from('admin:admin').toString('base64')}`, // ya X-Api-Key
      },
      body: JSON.stringify(payload),
    });

    if (!res.ok) {
      throw new Error(`CRM Error: ${res.statusText}`);
    }

    const result = await res.json();
    return NextResponse.json({ success: true, leadId: result.id });
  } catch (error) {
    console.error('Failed to submit lead to CRM:', error);
    return NextResponse.json({ success: false, error: error.message }, { status: 500 });
  }
}
```

---

## 🔒 Production API Key Kaise Banayein (Recommended)

Production me `admin:admin` password use karne ke bajaye:
1. CRM me jayein: **Administration** ➔ **API Users** ➔ **Create API User**.
2. Name: `Website Contact Form`.
3. Role: `Lead Creator` (sirf Lead create karne ki permission).
4. Generated **API Key** ko apni website ke `.env` file me `CRM_API_KEY` set karein.
