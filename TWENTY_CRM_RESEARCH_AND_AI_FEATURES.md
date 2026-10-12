# 🔬 Twenty CRM (`twentyhq/twenty`) Research & AI Automation Architecture

## 1. Twenty CRM Overview & Deep Research

**Twenty** ([github.com/twentyhq/twenty](https://github.com/twentyhq/twenty)) ek modern, open-source CRM hai (YC W23 backed) jo Salesforce aur HubSpot ka modern alternative banne ke liye develop kiya ja raha hai.

### 🌟 Twenty CRM ke Key Highlights:
1. **Tech Stack:**
   - **Frontend:** React, TypeScript, Jotai, Tailwind/Linaria (Notion-style sleek, minimalist UI).
   - **Backend:** NestJS, TypeScript, PostgreSQL, Redis, BullMQ.
   - **Protocol:** GraphQL + REST API + Webhooks.
2. **Standout Features:**
   - **Modern Notion-like Table & Kanban:** Instant inline editing, customizable column widths, real-time filtering.
   - **Command Palette (`Ctrl + K` / `Cmd + K`):** Fast keyboard-driven navigation across leads, companies, and actions.
   - **AI & MCP (Model Context Protocol) Support:** AI agents (Claude, Gemini, etc.) directly CRM ke records ko query, summarize, aur enrich kar sakte hain.
   - **Activity Timeline & Rich Notes:** Client ke saath hui meetings, emails, aur quick notes ka consolidated feed.

---

## 2. Twenty CRM ke Best Features jo Humne Aapke CRM me Port kiye hain:

Humne Twenty CRM ke signature AI aur productivity features ko **MaaJanki Web Tech CRM** me integrate kar diya hai:

### 🤖 Feature A: Real-Time AI Lead Scoring & Sentiment Engine
- Har lead ka profile (Industry, Website status, Notes, Sentiment) analyze karke automatically categorize karta hai:
  - **🔥 Hot Lead (Score: 80 - 100):** Aise high-ticket businesses jinki koi website nahi hai ya website broken/Google Site hai (MaaJanki Web Tech ke liye sabse aasan aur bada opportunity).
  - **⚡ Warm Lead (Score: 50 - 79):** Jinki website hai par SEO ranking nahi hai, ya inquiry stage me hain.
  - **❌ Cold / DNC (Score: 0 - 20):** Hostile response ya not interested leads ko automatically Do Not Contact mark karta hai.

### 💡 Feature B: AI Next-Best-Action Recommendation
- Sales executive ko sochna nahi padta ki next kya karna hai. CRM automatically batata hai:
  - *Example:* "🔥 High-Ticket Opportunity: Established business with NO website. Pitch modern portfolio website to showcase past projects."

### 📝 Feature C: Auto-Generated AI Pitch (WhatsApp / Email Outreach)
- Har lead ke business name, category, aur problem ke hisaab se personalized, ready-to-send outreach pitch ready rehta hai jise 1-click me copy karke WhatsApp ya Email par bheja ja sakta hai.

### ⚡ Feature D: Real-Time Auto-Enrichment Hook
- Chahe lead website contact form se aaye, Excel se import ho, ya manually add ki jaye — **EspoCRM Hook ([`AiLeadEnricher.php`](file:///d:/EspoCRM-10.0.9/custom/Espo/Custom/Hooks/Lead/AiLeadEnricher.php))** save hone se pehle hi real-time me AI analysis add kar deta hai.

---

## 3. Comparison: EspoCRM vs Twenty CRM

| Criteria | EspoCRM 10 (Current Setup) | Twenty CRM |
| :--- | :--- | :--- |
| **System Footprint** | **Super Lightweight (100MB - 200MB RAM)**, PHP 8.3 + MariaDB | Heavy (Requires Docker, Node.js, NestJS, Postgres, Redis, BullMQ, 2GB+ RAM) |
| **Email IMAP/SMTP** | **Built-in Full Two-way Email Sync** (Inbox, Folders, Auto-tracking) | Limited / Requires external microservices |
| **BPM / Workflows** | Built-in No-code Visual Workflow & Formula Engine | Workflow builder in active development |
| **MaaJanki AI Customization** | **Fully Configured & Working Locally** with custom Indian currency (INR), Lead categories & AI scoring | General SaaS CRM |
| **UI Experience** | Traditional Enterprise Single-Page Application (Fast & Reliable) | Notion-style Modern Minimalist |

---

## 4. Next Step: AI Outreach Command & Usage

Aap kabhi bhi terminal me command run karke sabhi leads ka AI analysis rerun kar sakte hain:
```bash
php bin/command ai-analyze-leads
```
Aur browser me **[http://localhost:8080/#Lead](http://localhost:8080/#Lead)** par jakar kisi bhi lead ko open karenge, toh **"AI Intelligence & Automation"** panel me uska complete score aur ready pitch dikhega!
