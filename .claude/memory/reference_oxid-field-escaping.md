---
name: oxid-field-escaping
description: OXID escapes twice — admin request layer entity-encodes input, Field T_TEXT ->value htmlspecialchars()es again; never feed ->value into GD/plaintext sinks
type: reference
---

# OXID field escaping — two encoding layers

Verified empirically in the shop container (2026-07-16, #219 review):

1. **Input layer:** admin saves go through `Request::getRequestEscapedParameter()` →
   `checkParamSpecialChars()` which entity-encodes `& " ' < >`. So the DB stores
   `H&amp;M GmbH` when the operator types `H&M GmbH`.
2. **Read layer:** `Field` with `T_TEXT` (the default in `BaseModel::_setFieldData`)
   escapes AGAIN on `->value` access (`Field::__get` → `getStr()->htmlspecialchars`,
   double_encode on). Reading `->value` of an admin-saved field yields
   `H&amp;amp;M GmbH`.

Consequences:
- `->value` is NOT plaintext and NOT single-encoded HTML — for admin-entered data it
  is double-encoded. Feeding it to GD (`imagettftext`), emails (plain), PDFs, or any
  non-HTML sink prints literal `&amp;amp;` garbage.
- To get true plaintext: `getRawFieldData('<field>')` (DB form, single-encoded) then
  `html_entity_decode(..., ENT_QUOTES)`.
- Templates that add `|escape:"html"` on top of `->value` triple-encode.

Found during #219: `Article::getGuaranteeGuarantor()/getGuaranteeModel()/getGuaranteeConditions()`
returned `->value` and the guarantor/model got composited onto the official EU label
PNG — any name with `&` would render corrupted. Tests missed it because fixtures used
`ACME GmbH` (no special chars). Always include `&`, quotes and `<b>` in text-field fixtures.
