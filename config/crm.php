<?php

return [
    'navigation' => [
        'Master Data' => [
            ['customers', 'Customers', 'Customer profiles, contacts and service history'],
            ['brands', 'Brand Master', 'Machine brand names'],
            ['departments', 'Department Master', 'Department names'],
            ['machine-categories', 'Machine Category Master', 'Machine category names'],
            ['skills', 'Technician Skills', 'Skill master, levels and certifications'],
            ['amc-plans', 'AMC Plan Master', 'Plan types, duration and pricing'],
            ['machines', 'Machines', 'Assets, documents and maintenance history'],
            ['technicians', 'Technicians', 'Staff profiles, employment and salary structure'],
        ],
        'Parts & Inventory' => [
            ['parts', 'Parts Master', 'Catalog, prices and minimum stock'],
            ['suppliers', 'Suppliers', 'Supplier contacts and tax information'],
            ['inventory', 'Inventory', 'Stock in, stock out and low stock'],
            ['parts-issues', 'Parts Issue / Return', 'Technician issues, usage and returns'],
            ['job-parts', 'Parts Used in Job', 'Job-wise consumption and warranty'],
            ['parts-requests', 'Parts Requests', 'Request, approval and issue workflow'],
        ],
        'Service Operations' => [
            ['service-requests', 'Service Requests', 'Complaints, priority and assignment'],
            ['assignments', 'Work Assignment', 'Availability, workload and skills'],
            ['job-cards', 'Job Cards', 'Scheduling and field service execution'],
            ['work-status', 'Work Status', 'Live status timeline and history'],
            ['service-reports', 'Service Reports', 'Diagnosis, work performed and photos'],
            ['service-history', 'Service History', 'Customer and machine timelines'],
        ],
        'Maintenance' => [
            ['amc', 'AMC Management', 'Contracts, service balance and expiry'],
            ['preventive-maintenance', 'Preventive Maintenance', 'Recurring schedules and due work'],
        ],
        'Workforce' => [
            ['attendance', 'Attendance', 'Daily, monthly and bulk attendance'],
            ['leave', 'Leave Management', 'Leave types, requests and approvals'],
            ['salary', 'Technician Salary', 'Payroll calculation and salary slips'],
            ['expenses', 'Technician Expenses', 'Claims, receipts and approvals'],
        ],
        'Insights & Control' => [
            ['notifications', 'Notifications', 'Operational alerts and reminders'],
            ['reports', 'Reports & Analytics', 'Technician, customer, machine and stock reports'],
            ['roles', 'Roles & Permissions', 'Role access and authorization matrix'],
            ['settings', 'Settings', 'Company, payroll, attendance and workflow settings'],
        ],
    ],
];
