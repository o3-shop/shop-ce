---
name: Form input must survive validation errors
description: When any form submission is rejected, the user's typed values must be re-rendered into the form unchanged — never make a user re-type after a server-side error
type: feedback
---

When any form (admin or storefront) is submitted and the server rejects the submission for any reason — validation failure, business-rule rejection, missing-asset gate, CSRF mismatch, anything — the form **MUST** be re-rendered with every value the user just typed pre-filled. The user only has to fix the offending part; they never re-type the rest.

This applies to every form in the system, including:
- Admin config forms (theme settings, payment methods, shipping rules, **the revocation activation toggle** that triggered this rule)
- Storefront forms (registration, checkout, contact, **the revocation form itself**, password reset)
- Multi-step flows (the user has already typed values across earlier steps; rejection at step N must not reset earlier steps)

**Concrete rule for new code:**

1. On the rejection path, do **not** `redirect()` to a fresh GET of the form — that loses POST data.
2. Re-render the same form template directly from the controller, passing the just-submitted values back as the form's pre-fill source.
3. Apply error styling / messages around the offending fields. Untouched fields render with their submitted values.
4. Sensitive fields that genuinely should not round-trip (passwords, CVC, card numbers, anti-spam answers): explicitly clear them and tell the user *why*. Default-clearing other fields is wrong.
5. Whenever you reach for `Registry::getUtils()->redirect(...)` after a failed validate, stop and ask whether you just deleted the user's work. The answer is almost always yes; render the same view with the data instead.

**Why:** retyping is one of the most reliable causes of form abandonment. A consumer who has filled in a 6-field revocation form, hit submit, and seen everything cleared because of a CAPTCHA failure will probably not retry; an operator who flipped six toggles in admin and saw five lost because the sixth failed validation will lose trust in the platform. The cost of preserving input is one extra `setViewData()` call; the cost of not preserving it is real users walking away. Captured during scoping of issue #99 / change `add-electronic-revocation` on 2026-04-26.

**How to apply:** any time you write a controller action that *might* reject a submission, the rejection branch must end in "render the form template again with the submitted values" — not "redirect to a clean form" and not "render an error page". When reviewing PRs, look for `redirect()` after a `validate(...) === false` and challenge it.
