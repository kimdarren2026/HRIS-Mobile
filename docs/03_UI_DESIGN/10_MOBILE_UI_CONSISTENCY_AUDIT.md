# Phase 32 — Mobile UI Consistency Audit

## Purpose

Phase 32 documents the current mobile UI consistency findings and a safe future implementation plan. This phase is documentation-only and does not change Laravel application code, Blade files, environment files, deployment configuration, migrations, npm/composer dependencies, or artisan state.

Payroll and payslip pages remain historical/deferred. They should not be expanded until Phase 34 Payroll External Integration Contract is defined, because HRIS Mobile is the source of truth for employee data and attendance only. Payroll calculation/payment belongs to the future external payroll system, and HRIS will only receive payroll/payslip results later.

## Current Findings

- Most Blade pages are standalone HTML documents with their own `<!DOCTYPE>`, `<html>`, `<head>`, and `<body>`.
- Only `resources/views/pages/preview/index.blade.php` currently extends `layouts.mobile`.
- `layouts.mobile` exists but is still minimal and not yet a full production mobile shell.
- Many pages repeat Tailwind CDN, Google Fonts, Material Symbols, and Tailwind config.
- Some pages duplicate Material Symbols imports.
- Body classes are inconsistent across modules, such as `bg-background`, `bg-surface`, `shadow-2xl`, `border-x`, `pb-24`, `pb-[72px]`, and `pb-40`.
- Components such as `flash-message`, `validation-errors`, `notification-bell`, and `audit-bottom-nav` exist but are not used consistently everywhere.
- Payroll/payslip pages are historical/deferred and should not be expanded until external payroll integration details are available.

## Audit Scope

- Login
- Employee dashboard
- Admin dashboard
- Finance dashboard
- Attendance check-in, outside-radius, and history
- Leave request and history
- HR approval queue
- HR employee list, form, and detail
- Admin users
- Notifications
- Profile
- Finance expenses
- Settings
- Error pages 403/404/500

## Proposed Mobile UI Standard

- Use one shared mobile shell/layout strategy.
- Use a consistent viewport meta configuration.
- Use a consistent max mobile width, recommended `max-w-[390px] mx-auto`.
- Use consistent background and surface tokens.
- Use a consistent header pattern.
- Use consistent spacing tokens.
- Use consistent flash and validation error placement.
- Use consistent notification bell placement for authenticated pages.
- Use consistent bottom navigation behavior per role.
- Avoid expanding payroll UI until Phase 34 contract is defined.

## Recommended Implementation Approach

| Phase | Scope | Notes |
| --- | --- | --- |
| Phase 32A | Documentation audit only | Capture current findings and future implementation plan without changing application code. |
| Phase 32B | Create or improve shared layout/components | Define the production mobile shell, shared head imports, viewport meta, surfaces, spacing, and navigation patterns. |
| Phase 32C | Migrate low-risk pages first | Start with pages that have limited form, script, GPS, or authorization behavior. |
| Phase 32D | Migrate complex pages | Move carefully through attendance check-in and HR approval queue after the shell is stable. |
| Phase 32E | Regression test role dashboards and navigation | Re-check all role dashboards, authenticated navigation, notifications, flash messages, validation errors, and mobile width behavior. |

## Risks

- Refactoring all Blade pages at once can break layout, scripts, GPS check-in, notification actions, and role navigation.
- Attendance check-in page has GPS and CSRF-sensitive behavior.
- HR approval queue has authorization-sensitive behavior.
- Payroll pages should remain historical/deferred.

## Acceptance Criteria

- Audit document exists.
- No application code changed.
- No Blade files changed.
- Phase 32 findings and implementation plan are documented.
- Future refactor is clearly separated from documentation audit.
