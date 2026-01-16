<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Program;
use Illuminate\Database\Seeder;

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        // Create sample programs
        Program::create([
            'title' => 'Web Development Bootcamp',
            'slug' => 'web-development-bootcamp',
            'category' => 'bootcamp',
            'description' => 'Master full-stack web development with modern frameworks',
            'overview' => 'A comprehensive 12-week intensive bootcamp covering frontend, backend, and full-stack development. Learn React, Node.js, and build real-world applications.',
            'who_is_for' => 'Anyone passionate about building web applications. No prior experience necessary, but basic programming knowledge is helpful.',
            'skills_and_tools' => 'React, Vue.js, Node.js, Express, MongoDB, PostgreSQL, Docker, Git, AWS',
            'duration' => '12 weeks',
            'learning_outcomes' => 'Build full-stack web applications. Understand responsive design principles. Deploy applications to production. Work with modern development tools and workflows.',
            'certification' => 'Traitz Academy Web Development Certification - Industry recognized, valid globally',
            'price' => 150000,
            'image_url' => 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=600&h=400',
            'is_featured' => true,
            'is_active' => true,
            'capacity' => 30,
            'enrolled_count' => 18,
            'start_date' => now()->addDays(30),
            'end_date' => now()->addDays(115),
            'curriculum' => 'Week 1-2: HTML, CSS, JavaScript Fundamentals
Week 3-4: React Basics & Components
Week 5-6: Advanced React & State Management
Week 7-8: Node.js & Express Backend
Week 9-10: Database Design & Queries
Week 11-12: Full-stack Project & Deployment',
        ]);

        Program::create([
            'title' => 'Data Science & Analytics',
            'slug' => 'data-science-analytics',
            'category' => 'professional-training',
            'description' => 'Learn data analysis, visualization, and machine learning',
            'overview' => 'Comprehensive 8-week training in data science fundamentals using Python, statistics, and real datasets.',
            'who_is_for' => 'Professionals looking to transition into data roles or enhance their analytics skills.',
            'skills_and_tools' => 'Python, Pandas, NumPy, Matplotlib, Scikit-learn, SQL, Tableau, Power BI',
            'duration' => '8 weeks',
            'learning_outcomes' => 'Analyze complex datasets. Build predictive models. Create compelling data visualizations. Communicate insights effectively.',
            'certification' => 'Traitz Academy Data Science Certificate',
            'price' => 100000,
            'image_url' => 'https://images.unsplash.com/photo-1551462147-ff29053bbb20?w=600&h=400',
            'is_featured' => true,
            'is_active' => true,
            'capacity' => 25,
            'enrolled_count' => 12,
            'start_date' => now()->addDays(45),
            'end_date' => now()->addDays(100),
            'curriculum' => 'Week 1: Python fundamentals & data structures
Week 2: Data manipulation with Pandas
Week 3: Data visualization & exploration
Week 4: Statistics & probability
Week 5-6: Machine learning algorithms
Week 7: Real-world projects
Week 8: Portfolio building & best practices',
        ]);

        Program::create([
            'title' => 'UI/UX Design Intensive',
            'slug' => 'ui-ux-design-intensive',
            'category' => 'bootcamp',
            'description' => 'Design beautiful and functional user interfaces',
            'overview' => '10-week intensive program covering design principles, tools, and user research methodologies.',
            'who_is_for' => 'Creative individuals interested in design. No design background needed.',
            'skills_and_tools' => 'Figma, Adobe XD, Sketch, Prototyping, User Research, Wireframing, Design Systems',
            'duration' => '10 weeks',
            'learning_outcomes' => 'Design professional interfaces. Understand user psychology. Build design systems. Create compelling prototypes.',
            'certification' => 'Traitz Academy UI/UX Design Certificate',
            'price' => 120000,
            'image_url' => 'https://images.unsplash.com/photo-1561070791-2526d30994b5?w=600&h=400',
            'is_featured' => true,
            'is_active' => true,
            'capacity' => 20,
            'enrolled_count' => 15,
            'start_date' => now()->addDays(15),
            'end_date' => now()->addDays(85),
            'curriculum' => 'Week 1-2: Design fundamentals & color theory
Week 3-4: Typography & layout principles
Week 5: Figma mastery
Week 6: User research methods
Week 7-8: Wireframing & prototyping
Week 9: Design systems
Week 10: Portfolio projects',
        ]);

        Program::create([
            'title' => 'Academic Internship Program',
            'slug' => 'academic-internship',
            'category' => 'academic-internship',
            'description' => 'Structured internship for university students',
            'overview' => 'A comprehensive academic internship program designed for university and school students. Gain real-world experience while fulfilling your academic requirements.',
            'who_is_for' => 'University and school students seeking industrial training or internship credits.',
            'skills_and_tools' => 'Professional software development practices, teamwork, project management',
            'duration' => 'Flexible (1-2 semesters)',
            'learning_outcomes' => 'Experience real development workflows. Apply academic knowledge practically. Build professional portfolio.',
            'certification' => 'Academic Internship Certificate with supervisor evaluation',
            'price' => 0,
            'image_url' => 'https://images.unsplash.com/photo-1552664730-d307ca884978?w=600&h=400',
            'is_featured' => true,
            'is_active' => true,
            'capacity' => 50,
            'enrolled_count' => 25,
            'start_date' => now()->addDays(7),
            'end_date' => now()->addDays(180),
            'curriculum' => 'Custom curriculum based on student background and institution requirements. Includes mentorship, real project work, and supervision.',
        ]);

        Program::create([
            'title' => 'Professional Internship',
            'slug' => 'professional-internship',
            'category' => 'professional-internship',
            'description' => 'Career-focused internship for early-career professionals',
            'overview' => 'Intensive 3-month professional internship program designed to bridge the gap between learning and employment.',
            'who_is_for' => 'Recent graduates and early-career professionals seeking hands-on experience.',
            'skills_and_tools' => 'Professional development, industry practices, portfolio building',
            'duration' => '3 months',
            'learning_outcomes' => 'Develop industry-relevant skills. Build professional network. Create impressive portfolio. Get placed in jobs.',
            'certification' => 'Professional Internship Certificate with performance evaluation',
            'price' => 80000,
            'image_url' => 'https://images.unsplash.com/photo-1552664730-d307ca884978?w=600&h=400',
            'is_featured' => true,
            'is_active' => true,
            'capacity' => 40,
            'enrolled_count' => 20,
            'start_date' => now()->addDays(20),
            'end_date' => now()->addDays(112),
            'curriculum' => 'Week 1-2: Onboarding & skill assessment
Week 3-8: Real project assignments with mentorship
Week 9-10: Advanced training & specialization
Week 11-12: Placement preparation & interviews',
        ]);

        // Create sample events
        Event::create([
            'title' => 'Web Development Workshop',
            'slug' => 'web-development-workshop',
            'description' => 'Free workshop introducing modern web development. Learn HTML, CSS, and JavaScript basics in 3 hours.',
            'event_date' => now()->addDays(5)->setHour(14)->setMinute(0),
            'location' => 'Traitz Tech Office, Lagos',
            'is_online' => false,
            'event_url' => null,
            'capacity' => 50,
            'registered_count' => 32,
            'category' => 'workshop',
            'image_url' => 'https://images.unsplash.com/photo-1552664730-d307ca884978?w=600&h=400',
            'is_active' => true,
            'agenda' => '2:00 PM - Welcome & Introduction
2:15 PM - HTML & CSS Fundamentals
3:00 PM - Introduction to JavaScript
3:30 PM - Live coding demo
4:00 PM - Q&A Session',
        ]);

        Event::create([
            'title' => 'Tech Career Panel Discussion',
            'slug' => 'tech-career-panel',
            'description' => 'Hear from successful tech professionals about their career journeys. Q&A with industry leaders.',
            'event_date' => now()->addDays(12)->setHour(18)->setMinute(0),
            'location' => null,
            'is_online' => true,
            'event_url' => 'https://zoom.us/meeting/example',
            'capacity' => 200,
            'registered_count' => 87,
            'category' => 'webinar',
            'image_url' => 'https://images.unsplash.com/photo-1552664730-d307ca884978?w=600&h=400',
            'is_active' => true,
            'agenda' => '6:00 PM - Opening remarks
6:15 PM - Speaker 1: Career transitions in tech
6:35 PM - Speaker 2: Building a tech startup
6:55 PM - Speaker 3: Remote work strategies
7:15 PM - Open Q&A with panelists
8:00 PM - Networking & closing',
        ]);

        Event::create([
            'title' => 'Mobile App Development Webinar',
            'slug' => 'mobile-app-webinar',
            'description' => 'Deep dive into mobile app development with Flutter. Perfect for beginners and intermediate developers.',
            'event_date' => now()->addDays(20)->setHour(15)->setMinute(0),
            'location' => null,
            'is_online' => true,
            'event_url' => 'https://zoom.us/meeting/example',
            'capacity' => 300,
            'registered_count' => 156,
            'category' => 'webinar',
            'image_url' => 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=600&h=400',
            'is_active' => true,
            'agenda' => '3:00 PM - Introduction to Flutter
3:30 PM - Setting up development environment
4:00 PM - Building your first app
4:45 PM - Debugging & optimization tips
5:15 PM - Q&A',
        ]);

        Event::create([
            'title' => 'Data Science Bootcamp Info Session',
            'slug' => 'data-science-info',
            'description' => 'Learn about our Data Science program. Meet instructors, ask questions, and understand the curriculum.',
            'event_date' => now()->addDays(8)->setHour(19)->setMinute(0),
            'location' => 'Traitz Tech Office, Lagos',
            'is_online' => false,
            'event_url' => null,
            'capacity' => 40,
            'registered_count' => 28,
            'category' => 'meetup',
            'image_url' => 'https://images.unsplash.com/photo-1551462147-ff29053bbb20?w=600&h=400',
            'is_active' => true,
            'agenda' => '7:00 PM - Welcome & refreshments
7:15 PM - Program overview
7:35 PM - Instructor Q&A
8:00 PM - Alumni panel discussion
8:30 PM - Registration & closing',
        ]);
    }
}
