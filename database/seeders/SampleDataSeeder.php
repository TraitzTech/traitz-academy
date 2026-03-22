<?php

namespace Database\Seeders;

use App\Models\Program;
use Illuminate\Database\Seeder;

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        // Internship Opportunity – Traitz Tech
        // 6-month professional internships with stipend, mentorship, and real-world projects

        Program::create([
            'title' => 'Laravel Development Intern',
            'slug' => 'laravel-development-intern',
            'category' => 'professional-internship',
            'description' => '6-month professional internship in Laravel backend development at Traitz Tech. Work on production applications, receive dedicated mentorship, and earn a monthly stipend while building your career.',
            'overview' => 'We are recruiting a Laravel Development Intern to join our engineering team for a 6-month professional internship. You will work alongside senior engineers on our production platform — writing clean PHP code, designing database schemas, building RESTful APIs, and shipping features used by real users. This is a structured, hands-on internship with weekly mentorship sessions, code reviews, and a clear growth path. A small monthly stipend is provided to support you throughout the program.',
            'who_is_for' => 'Students, recent graduates, or career switchers with foundational PHP knowledge and an interest in backend development. You should understand basic programming concepts (variables, loops, functions, OOP) and have built at least one small project. Familiarity with Laravel, MySQL, or any MVC framework is a plus but not required — we will train you.',
            'skills_and_tools' => 'PHP 8, Laravel, MySQL, Redis, REST APIs, Git, Docker, Pest, PHPUnit, Postman, VS Code',
            'duration' => '6 months',
            'learning_outcomes' => 'Build and maintain Laravel applications following industry best practices. Design and optimize database schemas using Eloquent ORM and migrations. Develop secure RESTful APIs with proper authentication and validation. Write automated tests using Pest and PHPUnit for reliable, production-ready code. Understand deployment workflows using CI/CD pipelines and version control.',
            'certification' => 'Small monthly stipend provided for the duration of the internship. Professional Internship Certificate with detailed performance evaluation. Letter of recommendation from your supervising engineer. Priority consideration for full-time roles at Traitz Tech upon completion. Free access to all Traitz Academy learning programs.',
            'price' => 0,
            'image_url' => 'https://images.unsplash.com/photo-1555066931-4365d14bab8c?w=600&h=400',
            'is_featured' => true,
            'is_active' => true,
            'capacity' => 5,
            'enrolled_count' => 0,
            'start_date' => now()->addDays(14),
            'end_date' => now()->addDays(195),
            'curriculum' => 'Month 1: PHP fundamentals deep-dive, Laravel architecture & environment setup
Month 2: Database design, Eloquent relationships & query optimization
Month 3: RESTful API development, authentication & middleware
Month 4: Testing with Pest/PHPUnit, debugging & performance profiling
Month 5: Real project ownership — feature development on the Traitz platform
Month 6: Advanced patterns (queues, events, caching), code review lead & portfolio finalization',
        ]);

        Program::create([
            'title' => 'Flutter Development Intern',
            'slug' => 'flutter-development-intern',
            'category' => 'professional-internship',
            'description' => '6-month professional internship in Flutter mobile development at Traitz Tech. Build cross-platform mobile apps for iOS and Android, receive hands-on mentorship, and earn a monthly stipend.',
            'overview' => 'We are recruiting a Flutter Development Intern to join our mobile team for a 6-month professional internship. You will build and ship cross-platform mobile applications — implementing UI screens, integrating backend APIs, managing state, and optimizing performance for both iOS and Android. You will be mentored by an experienced mobile developer through daily standups, code reviews, and pair programming sessions. A small monthly stipend is provided to support you throughout the program.',
            'who_is_for' => 'Students or early-career developers with an interest in mobile app development. You should have basic programming knowledge in any language (Dart, Java, JavaScript, Python, etc.) and a genuine passion for building mobile experiences. Prior Flutter or mobile development experience is not required — we will train you from the ground up.',
            'skills_and_tools' => 'Dart, Flutter, Firebase, REST API Integration, State Management (Riverpod/Bloc), Git, Android Studio, Xcode, Figma (reading designs), VS Code',
            'duration' => '6 months',
            'learning_outcomes' => 'Build cross-platform mobile applications using Flutter and Dart. Implement responsive UI layouts that work beautifully across phones and tablets. Integrate RESTful APIs and manage application state effectively. Work with Firebase for authentication, push notifications, and analytics. Prepare and publish apps to Google Play Store and Apple App Store.',
            'certification' => 'Small monthly stipend provided for the duration of the internship. Professional Internship Certificate with detailed performance evaluation. Published mobile app in your professional portfolio. Letter of recommendation from your supervising engineer. Priority consideration for full-time mobile developer roles at Traitz Tech.',
            'price' => 0,
            'image_url' => 'https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?w=600&h=400',
            'is_featured' => true,
            'is_active' => true,
            'capacity' => 4,
            'enrolled_count' => 0,
            'start_date' => now()->addDays(14),
            'end_date' => now()->addDays(195),
            'curriculum' => 'Month 1: Dart language fundamentals & Flutter framework basics
Month 2: UI development — widgets, layouts, navigation & animations
Month 3: State management, API integration & local storage
Month 4: Firebase services — auth, Firestore, push notifications & analytics
Month 5: Real project ownership — feature development on the Traitz mobile app
Month 6: Testing, performance optimization, app store deployment & portfolio finalization',
        ]);

        Program::create([
            'title' => 'UI/UX Design Intern',
            'slug' => 'ui-ux-design-intern',
            'category' => 'professional-internship',
            'description' => '6-month professional internship in UI/UX design at Traitz Tech. Create user-centered designs for web and mobile products, receive expert mentorship, and earn a monthly stipend.',
            'overview' => 'We are recruiting a UI/UX Design Intern to join our product design team for a 6-month professional internship. You will work on real design challenges — conducting user research, creating wireframes, designing high-fidelity mockups, and building a scalable design system used across our platform. You will collaborate closely with product managers and engineers to bring your designs to life. A senior designer will mentor you through design thinking workshops, critique sessions, and portfolio development. A small monthly stipend is provided to support you throughout the program.',
            'who_is_for' => 'Aspiring designers, visual arts students, or creative professionals with a keen eye for detail and a passion for user experience. You should have basic familiarity with at least one design tool (Figma, Adobe XD, Sketch, or Canva). A portfolio showing any design work — personal projects, school assignments, or freelance work — is welcome. No professional design experience required.',
            'skills_and_tools' => 'Figma, Adobe Photoshop, Adobe Illustrator, Prototyping, Wireframing, User Research, Usability Testing, Design Systems, Color Theory, Typography, Accessibility (WCAG)',
            'duration' => '6 months',
            'learning_outcomes' => 'Conduct user research through interviews, surveys, and usability testing. Create wireframes, user flows, and information architecture diagrams. Design high-fidelity mockups and interactive prototypes in Figma. Build and maintain reusable design system components. Collaborate with developers using proper handoff practices and design specifications.',
            'certification' => 'Small monthly stipend provided for the duration of the internship. Professional Internship Certificate with portfolio review. Professional design portfolio with shipped product case studies. Letter of recommendation from your supervising designer. Free Figma Pro license during the internship and priority hiring for design roles.',
            'price' => 0,
            'image_url' => 'https://images.unsplash.com/photo-1586717791821-3f44a563fa4c?w=600&h=400',
            'is_featured' => true,
            'is_active' => true,
            'capacity' => 3,
            'enrolled_count' => 0,
            'start_date' => now()->addDays(14),
            'end_date' => now()->addDays(195),
            'curriculum' => 'Month 1: Design fundamentals — principles, color theory, typography & Figma mastery
Month 2: User research methods — personas, journey maps & competitive analysis
Month 3: Wireframing, prototyping & information architecture
Month 4: Design system creation — component libraries, tokens & documentation
Month 5: Real project ownership — redesign key flows on the Traitz platform
Month 6: Usability testing, design critique leadership & portfolio case study finalization',
        ]);

        Program::create([
            'title' => 'Frontend Web Development Intern',
            'slug' => 'frontend-web-development-intern',
            'category' => 'professional-internship',
            'description' => '6-month professional internship in frontend web development at Traitz Tech. Build modern, responsive interfaces using React (Next.js) or Vue.js, receive dedicated mentorship, and earn a monthly stipend.',
            'overview' => 'We are recruiting a Frontend Web Development Intern to join our engineering team for a 6-month professional internship. You will build responsive, accessible, and performant user interfaces for our web platform using modern JavaScript frameworks — React with Next.js or Vue.js depending on the project. From pixel-perfect UI implementation to complex interactive features, you will gain production experience in the frontend stack that powers modern web applications. Your mentor will guide you through component architecture, state management, and professional development workflows. A small monthly stipend is provided to support you throughout the program.',
            'who_is_for' => 'Students or self-taught developers with a solid foundation in HTML, CSS, and JavaScript. You should understand basic DOM manipulation, responsive design concepts, and have built at least one web page or small project. Experience with any JavaScript framework (React, Vue, Next.js, Angular) or CSS framework (Tailwind CSS, Bootstrap) is a bonus but not required. A strong visual sense and attention to detail will help you thrive.',
            'skills_and_tools' => 'React, Next.js, Vue.js 3, TypeScript, Tailwind CSS, HTML5, CSS3, JavaScript (ES6+), Git, Vite, ESLint, Prettier, Chrome DevTools, VS Code',
            'duration' => '6 months',
            'learning_outcomes' => 'Build responsive and accessible web interfaces using React (Next.js) or Vue.js. Write type-safe frontend code with TypeScript and modern JavaScript patterns. Implement complex UI components — forms, modals, data tables, and animations. Optimize frontend performance with lazy loading, code splitting, and caching strategies. Collaborate with designers and backend developers for seamless full-stack delivery.',
            'certification' => 'Small monthly stipend provided for the duration of the internship. Professional Internship Certificate with detailed performance evaluation. Letter of recommendation from your supervising engineer. Priority consideration for full-time frontend roles at Traitz Tech. Free access to all Traitz Academy programs and a professional GitHub portfolio.',
            'price' => 0,
            'image_url' => 'https://images.unsplash.com/photo-1547658719-da2b51169166?w=600&h=400',
            'is_featured' => true,
            'is_active' => true,
            'capacity' => 4,
            'enrolled_count' => 0,
            'start_date' => now()->addDays(14),
            'end_date' => now()->addDays(195),
            'curriculum' => 'Month 1: HTML5, CSS3 & JavaScript fundamentals deep-dive
Month 2: React fundamentals — components, hooks, routing & Next.js basics (or Vue.js 3 equivalent)
Month 3: TypeScript essentials & Tailwind CSS utility-first styling
Month 4: Advanced patterns — state management, data fetching & form handling
Month 5: Real project ownership — build production features on the Traitz platform
Month 6: Performance optimization, accessibility auditing, testing & portfolio finalization',
        ]);
    }
}
