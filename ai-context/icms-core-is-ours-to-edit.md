# The ICMS core is ours to edit

`libraries/icms/`, `kernel/`, `class/` and the other core ImpressCMS/XOOPS parts of this repo are a
Formulize fork, not a vendored upstream dependency. Nobody runs this ICMS without Formulize.

**How to apply:**

- Editing core files directly is normal and expected. Some older changes are marked with a
  `ALTERED BY FREEFORM SOLUTIONS` comment, but that is from a time when changes might have been shared upstream. This is no longer a concern, so never add these comments or anything like them to new changes. Leave the existing ones as they are, because they're a historical record.
- Do **not** wrap calls into Formulize functions from core code in `function_exists()` guards "in
  case Formulize isn't loaded." That was flagged as pointless defensiveness. The process for bootstrapping ICMS is to call `mainfile.php` (which calls `include/common.php`) and at the end of `include/common.php` all Formulize classes and functions are included as well (see [bootstrap-include-order](bootstrap-include-order.md)).
- The one genuine exception is code reachable *before* that bootstrap is complete: the `icms::boot()` happens early in `include/common.php` and the standalone installer under `install/` never loads it at
  all.
