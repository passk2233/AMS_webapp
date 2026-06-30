---
target: views/evaluate.php
total_score: 29
p0_count: 0
p1_count: 0
timestamp: 2026-06-29T14-05-18Z
slug: views-evaluate-php
---
# Critique — views/evaluate.php (student evaluation form)

Register: product. Theme: Hope UI blue (#3a57e8). Detector: clean (exit 0). Reviewed post-harden.

## Design Health Score

| # | Heuristic | Score | Key Issue |
|---|-----------|-------|-----------|
| 1 | Visibility of System Status | 3 | Submit shows busy text but no spinner on a multi-second N-call submit; no per-form progress |
| 2 | Match System / Real World | 3 | Fully Lao UI, but the native `required` validation popup is English |
| 3 | User Control and Freedom | 3 | Back link present; navigating back mid-form silently discards entered scores |
| 4 | Consistency and Standards | 3 | Shared vocabulary; one inline `style="margin:0"` remains on the comment label |
| 5 | Error Prevention | 3 | min/max/required/clamp, no invalid 0 default, double-submit guard, input preserved on error |
| 6 | Recognition Rather Than Recall | 3 | Subject/teacher header + collapsible scale legend; legend collapsed by default |
| 7 | Flexibility and Efficiency | 2 | No accelerators, no jump-to-unscored, no quick-fill |
| 8 | Aesthetic and Minimalist Design | 3 | Monotonous stack of identical white cards, one per question, each with a heavy stepper |
| 9 | Help Users Recover from Errors | 3 | role=alert + red outline; no aria-invalid, no focus/scroll to first error |
| 10 | Help and Documentation | 3 | Scoring legend now on the form (was the worst gap at the project level) |
| **Total** | | **29/40** | **Good — the harden pass moved this from Acceptable to Good** |

## Anti-Patterns Verdict

**Does this look AI-generated? No.** Detector returns clean (exit 0, no findings). No banned patterns. The custom score stepper is a justified mobile affordance for a 1–10 scale, not a gratuitous reinvention; +/- buttons flanking a number input is a standard pattern. On-brand, restrained, fully Lao copy.

## What's Working

1. **The harden pass landed.** No invalid `0` default, inputs and comment preserved on validation error, double-submit guard, busy-text on submit, red field outlines tied to a role=alert message. These were the project-level P1s and they're addressed.
2. **The scoring legend is now where it's needed.** `score_legend()` surfaced as a collapsible on the form itself — students see what 8 vs 5 means at the moment they choose, not just admins on the report.
3. **Accessible label wiring.** Each score input is `aria-labelledby` its question text (replaced an English numeric aria-label), and the +/- buttons carry Lao aria-labels.

## Priority Issues

- **[P2] Thin feedback on a long, slow submit.** No per-form progress ("X/N scored") and the submit fires N sequential API calls (seconds) with only a text swap to "ກຳລັງສົ່ງ...". On a 15-question form the student can't gauge remaining work or trust that a multi-second submit is progressing. Fix: live scored-count that updates on input; spinner glyph on the busy button. Command: /impeccable animate.
- **[P2] Back / browser-back discards all entered scores.** The new back link (and native back) loses work with no draft or confirm — the interruption case for a one-handed mobile student. Fix: persist a per-plan localStorage draft (restore on return) or confirm-on-dirty. Command: /impeccable harden.
- **[P2] Errored fields aren't programmatically invalid and there's no jump-to-error.** Red outline is visual-only; no `aria-invalid="true"`, and the role=alert doesn't move focus to the first red field. A screen-reader or no-JS user is told to "fix the red items" but can't locate them. Fix: aria-invalid when missing; focus/scroll first invalid input on error. Command: /impeccable clarify.
- **[P3] Monotonous identical-card stack.** One white card per question, each holding a heavy 54px stepper, repeats with no rhythm on a long form. Consider grouping a category's questions into one card with dividers, or a lighter row per question. Command: /impeccable layout.
- **[P3] i18n + placeholder contrast leaks.** Native `required` popup is English on an all-Lao screen (use setCustomValidity in Lao); the comment placeholder likely dips below 4.5:1 on the tinted input bg. Command: /impeccable harden.

## Persona Red Flags

**Casey (distracted mobile — primary):** back-tap data loss; no progress indicator to resume against; multi-second submit with thin reassurance.

**Sam (screen reader / keyboard):** errored inputs lack `aria-invalid`; no focus move to the first error; English native validation popup; tapping "−" on an unset field is a silent no-op.

**Jordan (first-timer):** an empty stepper may read as "skip / already done" rather than "enter a score"; the legend is collapsed so the meaning of 8 vs 5 is one tap away, not visible; "−" doing nothing when unset is unexplained.

## Minor Observations

- One inline `style="margin:0;"` remains on the comment label (line 62) — last inline style on this surface.
- Empty stepper has no visible placeholder/affordance prompting input.
- "−" on an unset field is a no-op with no feedback (briefly flash, or set to 1).
- Busy button is text-only; a spinner reads as "working" more clearly on multi-second ops.

## Questions to Consider

- On a 15-question form, does one card per question aid scanning or just add scroll? Would category-grouped rows scan faster?
- Should a half-filled evaluation survive a back-tap or a phone call?
- Does an empty stepper communicate "enter a score," or could it be mistaken for done?
