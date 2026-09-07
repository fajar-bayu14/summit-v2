---
trigger: always_on
---

# Frontend & UI Skill Routing

## Mandatory Frontend Skill Policy

Whenever a task involves creating, modifying, reviewing, or refactoring
frontend code or user interface, the relevant UI/UX skills MUST be used
before making any implementation changes.

Do NOT implement frontend/UI changes based only on general knowledge.
First inspect and apply the relevant skills below.

---

## Skill Routing

### 1. Frontend / UI changes

For ANY task that creates or modifies:

- pages
- screens
- layouts
- components
- forms
- buttons
- navigation
- modals
- dialogs
- tables
- cards
- dashboards
- responsive behavior
- spacing
- typography
- colors
- visual hierarchy
- animations
- interactions
- accessibility
- empty/loading/error states

MUST read/use:

- `skills/design-system`
- `skills/ui-styling`
- `skills/ui-ux-pro-max`

When appropriate, also use the more specific skills below.

---

### 2. Design System

MUST use:

- `skills/design-system`

when:

- creating a new component
- modifying an existing component's visual design
- establishing design tokens
- changing typography
- changing colors
- changing spacing
- defining component variants
- creating reusable UI patterns
- making changes that affect visual consistency across the application

Do not introduce new visual patterns if an existing design-system
pattern can be reused.

---

### 3. UI Styling

MUST use:

- `skills/ui-styling`

when:

- changing CSS
- changing Tailwind classes
- changing spacing
- changing sizing
- changing colors
- changing typography
- changing borders
- changing shadows
- changing radius
- changing responsive behavior
- changing visual states
- changing component appearance

---

### 4. UI/UX

MUST use:

- `skills/ui-ux-pro-max`

for any significant frontend/UI work.

This includes:

- creating new pages
- redesigning pages
- improving UX
- improving information hierarchy
- designing user flows
- responsive layouts
- forms
- dashboards
- complex interactions
- accessibility
- empty/loading/error states
- mobile/desktop behavior

Before implementation, consider:

- usability
- hierarchy
- consistency
- accessibility
- responsiveness
- interaction states
- visual clarity

---

### 5. General Design

Use:

- `skills/design`

when the task requires broader visual or product design decisions,
such as:

- page composition
- visual direction
- layout exploration
- design critique
- visual hierarchy
- interaction design
- UX decisions

---

### 6. Brand

Use:

- `skills/brand`

when the task involves:

- brand identity
- brand colors
- logo usage
- brand typography
- tone of voice
- branded UI
- marketing pages
- landing pages
- visual identity

---

### 7. Banner Design

Use:

- `skills/banner-design`

when creating or modifying:

- banners
- hero graphics
- promotional banners
- announcement banners
- campaign visuals
- marketing graphics

---

### 8. Slides / Presentation

Use:

- `skills/slides`

when creating or modifying:

- presentations
- slide decks
- pitch decks
- presentation layouts
- presentation visuals

---

## Skill Selection Rules

When multiple skills apply, use all relevant skills.

For example:

### New application page

Use:

1. `skills/ui-ux-pro-max`
2. `skills/design-system`
3. `skills/ui-styling`

### New branded landing page

Use:

1. `skills/ui-ux-pro-max`
2. `skills/design`
3. `skills/design-system`
4. `skills/ui-styling`
5. `skills/brand`

### Modify existing button styling

Use:

1. `skills/design-system`
2. `skills/ui-styling`
3. `skills/ui-ux-pro-max` when the change affects UX or interaction

### Create marketing banner

Use:

1. `skills/banner-design`
2. `skills/brand` if brand identity is involved
3. `skills/ui-ux-pro-max` if the banner is part of an application UI

---

## Mandatory Workflow

For frontend/UI tasks, follow this order:

1. Identify whether the task affects frontend/UI.
2. Identify the relevant skills.
3. Read/use the relevant skills BEFORE editing code.
4. Inspect the existing implementation and design patterns.
5. Reuse existing components and design-system patterns whenever possible.
6. Implement the smallest appropriate change.
7. Validate responsive behavior and visual consistency.
8. Run relevant tests/type checks/build.
9. Review the final diff for unintended visual changes.

---

## Important

"Frontend task" includes more than creating a new page.

Treat the task as a frontend/UI task if the request changes
what the user sees, interacts with, or experiences.

Examples:

- "Make this button smaller" → frontend/UI task
- "Change the dashboard layout" → frontend/UI task
- "Add dark mode" → frontend/UI task
- "Improve mobile layout" → frontend/UI task
- "Make this form easier to use" → frontend/UI task
- "Change the table spacing" → frontend/UI task
- "Fix the visual alignment" → frontend/UI task
- "Add a loading state" → frontend/UI task
- "Create an API endpoint" → NOT automatically a frontend task
- "Refactor backend service" → NOT automatically a frontend task

When uncertain whether a change affects UI, treat it as a frontend/UI
task and inspect the relevant UI skills first.
