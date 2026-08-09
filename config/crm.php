<?php

return [
    'navigation' => [
        'Master Data' => [
            ['customers', 'Customers', 'Customer profiles, contacts and service history'],
            ['brands', 'Brand Master', 'Machine brand names'],
            ['departments', 'Department Master', 'Department names'],
            ['skills', 'Technician Skills', 'Skill master, levels and certifications'],
            ['machines', 'Machines', 'Assets, documents and maintenance history'],
            ['technicians', 'Technicians', 'Staff profiles, employment and salary structure'],
        ],
        'Workforce' => [
            ['attendance', 'Attendance', 'Daily, monthly and bulk attendance'],
            ['leave', 'Leave Management', 'Leave types, requests and approvals'],
            ['salary', 'Technician Salary', 'Payroll calculation and salary slips'],
            ['expenses', 'Technician Expenses', 'Claims, receipts and approvals'],
        ],
        'Service Operations' => [
            ['service-requests', 'Service Requests', 'Complaints, priority and assignment'],
            ['job-cards', 'Job Cards', 'Scheduling and field service execution'],
            ['assignments', 'Work Assignment', 'Availability, workload and skills'],
            ['work-status', 'Work Status', 'Live status timeline and history'],
            ['service-reports', 'Service Reports', 'Diagnosis, work performed and photos'],
            ['service-history', 'Service History', 'Customer and machine timelines'],
            ['ratings', 'Signatures & Ratings', 'Completion approval and feedback'],
        ],
        'Parts & Inventory' => [
            ['parts', 'Parts Master', 'Catalog, prices and minimum stock'],
            ['suppliers', 'Suppliers', 'Supplier contacts and tax information'],
            ['inventory', 'Inventory', 'Stock in, stock out and low stock'],
            ['parts-issues', 'Parts Issue / Return', 'Technician issues, usage and returns'],
            ['job-parts', 'Parts Used in Job', 'Job-wise consumption and warranty'],
            ['parts-requests', 'Parts Requests', 'Request, approval and issue workflow'],
        ],
        'Maintenance' => [
            ['amc', 'AMC Management', 'Contracts, service balance and expiry'],
            ['preventive-maintenance', 'Preventive Maintenance', 'Recurring schedules and due work'],
        ],
        'Insights & Control' => [
            ['notifications', 'Notifications', 'Operational alerts and reminders'],
            ['reports', 'Reports & Analytics', 'Technician, customer, machine and stock reports'],
            ['roles', 'Roles & Permissions', 'Role access and authorization matrix'],
            ['settings', 'Settings', 'Company, payroll, attendance and workflow settings'],
        ],
    ],
];
