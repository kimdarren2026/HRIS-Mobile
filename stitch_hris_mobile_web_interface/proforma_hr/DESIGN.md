---
name: ProForma HR
colors:
  surface: '#F9FAFB'
  surface-dim: '#d3daea'
  surface-bright: '#f9f9ff'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f0f3ff'
  surface-container: '#e7eefe'
  surface-container-high: '#e2e8f8'
  surface-container-highest: '#dce2f3'
  on-surface: '#151c27'
  on-surface-variant: '#464555'
  inverse-surface: '#2a313d'
  inverse-on-surface: '#ebf1ff'
  outline: '#777587'
  outline-variant: '#c7c4d8'
  surface-tint: '#4d44e3'
  primary: '#3525cd'
  on-primary: '#ffffff'
  primary-container: '#4f46e5'
  on-primary-container: '#dad7ff'
  inverse-primary: '#c3c0ff'
  secondary: '#4e45d5'
  on-secondary: '#ffffff'
  secondary-container: '#6860ef'
  on-secondary-container: '#fffbff'
  tertiary: '#7e3000'
  on-tertiary: '#ffffff'
  tertiary-container: '#a44100'
  on-tertiary-container: '#ffd2be'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#e2dfff'
  primary-fixed-dim: '#c3c0ff'
  on-primary-fixed: '#0f0069'
  on-primary-fixed-variant: '#3323cc'
  secondary-fixed: '#e3dfff'
  secondary-fixed-dim: '#c3c0ff'
  on-secondary-fixed: '#100069'
  on-secondary-fixed-variant: '#372abf'
  tertiary-fixed: '#ffdbcc'
  tertiary-fixed-dim: '#ffb695'
  on-tertiary-fixed: '#351000'
  on-tertiary-fixed-variant: '#7b2f00'
  background: '#f9f9ff'
  on-background: '#151c27'
  surface-variant: '#dce2f3'
  success: '#10B981'
  warning: '#F59E0B'
  danger: '#EF4444'
  border: '#E5E7EB'
typography:
  headline-lg:
    fontFamily: Inter
    fontSize: 24px
    fontWeight: '700'
    lineHeight: 32px
  headline-md:
    fontFamily: Inter
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
  body-lg:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  body-md:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  label-md:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '600'
    lineHeight: 16px
    letterSpacing: 0.05em
  label-sm:
    fontFamily: Inter
    fontSize: 11px
    fontWeight: '500'
    lineHeight: 14px
  status-badge:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '700'
    lineHeight: 12px
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  unit-xs: 4px
  unit-sm: 8px
  unit-md: 16px
  unit-lg: 24px
  unit-xl: 32px
  container-margin: 16px
  card-gap: 12px
---

## Brand & Style

The brand personality is **Professional, Dependable, and Efficient**. As an HRIS tool, the design must inspire trust and clarity while maintaining the speed required for daily operational tasks like clocking in or approving leave requests.

The chosen design style is **Corporate / Modern** with a **Mobile-First** priority. It leverages the "High-End SaaS" aesthetic: clean white space, a disciplined indigo-centric palette, and high-quality functional typography. The interface uses rounded containers and subtle depth to feel approachable for employees, while remaining structured enough for HR and Finance professionals.

**Key Visual Principles:**
- **Clarity over Decoration:** Every element must serve a functional purpose in the HR workflow.
- **Immediate Feedback:** Use distinct status colors (Indigo for primary actions, Green/Yellow/Red for state changes) to provide instant cognitive recognition of task status.
- **High-Density Utility:** Information is grouped into clear cards to avoid the "table fatigue" common in legacy HR software.

## Colors

The palette is anchored by **Primary Indigo (#4F46E5)** for main actions and branding, with **Primary Blue (#4338CA)** used for interactive states like hover or active navigation.

The system relies heavily on a clean **White (#FFFFFF)** background and a soft **Surface Gray (#F9FAFB)** for card backgrounds to create a layered, organized look without needing heavy lines.

**Functional Color Logic:**
- **Success (Green):** Used for "Approved" statuses and successful check-ins.
- **Warning (Amber):** Reserved for "Pending Review" or "Draft" states.
- **Danger (Red):** Used for "Rejected" statuses, "Clock Out" actions, or "Logout."
- **Border:** A consistent light gray is used for subtle separation in high-density data views.

## Typography

The design system utilizes **Inter** for all roles, capitalizing on its exceptional legibility on small mobile screens. The scale is intentionally tight to ensure maximum information density without sacrificing readability.

- **Headlines:** Use a bold weight to anchor sections and provide immediate context on what page the user is on.
- **Body Text:** Uses a standard 16px base for core content and 14px for secondary data descriptions.
- **Labels:** Uppercase letter-spacing is applied to small labels (like NIK or Department titles) to differentiate them from interactive data.
- **Mobile Scale:** On mobile devices, the largest headline size is capped at 24px to prevent excessive wrapping.

## Layout & Spacing

This design system follows a **Mobile-First Fluid Layout**. Elements are designed to fill the width of the viewport on small devices, with a standard side margin of 16px to prevent content from touching the screen edges.

**Grid Philosophy:**
- **Mobile (Default):** Single column stack. Cards are full-width minus margins.
- **Desktop (Responsive):** Elements reflow into a 12-column grid. Dashboard cards move from a vertical stack to a 2 or 3-column grid layout.
- **Spacing Rhythm:** Based on a 4px/8px baseline. Use 16px for standard internal padding and 24px for separating major sections (e.g., between the Attendance Card and Leave Balance Card).

## Elevation & Depth

Hierarchy is established through **Tonal Layering** and **Subtle Shadows**. 

- **Level 0 (Background):** The application background is pure White or the very light Surface Gray.
- **Level 1 (Cards/Inputs):** Elements sit on a Level 1 surface. On white backgrounds, these are defined by a 1px border (#E5E7EB). On light gray backgrounds, they use a soft shadow.
- **Shadow Style:** Use a "Small" shadow (`0px 1px 2px 0px rgba(0, 0, 0, 0.05)`) to give cards a slight lift from the background without making the UI feel cluttered or heavy.
- **Bottom Navigation:** Fixed to the bottom of the screen with a slight top border and a background blur (Backdrop Filter) to maintain visibility over scrolling content.

## Shapes

The shape language is defined as **Rounded**, leaning into a friendly but professional appearance.

- **Primary Container/Cards:** Use the `rounded-xl` (1.5rem / 24px) setting to create a distinct, modern "app-like" container feel for data displays.
- **Buttons & Inputs:** Use the standard `rounded` (0.5rem / 8px) setting for a crisp, functional look.
- **Badges:** Status badges use a full pill-shape (9999px) to clearly distinguish them from interactive buttons or text containers.

## Components

### Buttons
- **Primary:** Full-width on mobile. Solid Indigo (#4F46E5) with white text.
- **Secondary/Outline:** Bordered with Indigo text. Used for "View Details" or "Download PDF".
- **Action Buttons:** "Approve" (Solid Green) and "Reject" (Outline Red) should be prominent in HR queues.

### Cards (Data Displays)
- Instead of tables, all data is presented in cards. 
- Each card should have a clear title, a primary metric (e.g., "12 Days Left"), and a secondary status badge in the top right.

### Inputs
- **Text Inputs:** Light gray border, 16px padding. Labels sit above the field.
- **Attendance Capture:** A specialized input including a Map Preview block and a circular Camera Trigger.

### Status Badges
- Small, pill-shaped markers with high-contrast text.
- **Approved:** Green background, Dark Green text.
- **Pending:** Yellow background, Dark Amber text.
- **Rejected:** Red background, White text.

### Bottom Navigation
- Fixed height (approx 64px-72px).
- Icons with small labels below. Active state uses Primary Indigo; inactive uses Neutral Gray.

### Lists
- For attendance history, use a "row-card" format: Vertical stack on mobile, with the date on the left and the status badge on the right.