# Element references

Formulize has to find every place that refers to an element, for the usage report, for cleaning up after a delete, and for renaming a handle. Those places are declared once. When you add something that refers to an element, declare it, or those three features will miss it.

- **Settings and tables:** add the location to `$modversion['formulize_element_references']` in `xoops_version.php`, or to the element type handler's `static $eleValueReferences`. `formulizeElementReferences` (`class/elementReferences.php`) reads both. Never write a per-class scanner.
- **Function and method parameters:** give any new function or method that takes an element an `@formulizeElement` line in its docblock. `formulizeElementParameters` (`class/elementParameters.php`) reads them, so a rename can update quoted handles passed to them in any code. A method is matched by name alone, so never tag a method whose name another class uses. See [Declaring parameters that take elements](../docs/element_parameters.md).
- **Linked element sources:** read and write them only through `formulize_linkedSourceParts()` and `formulize_linkedSourceString()`. Never `explode()` them yourself.

## Dependency methods

`getElementDependencies()`, each element type's `getEleValueDependencies()`, and the `convertDependenciesForImport()` / `Export()` and `convertEleValueDependenciesForImport()` / `Export()` methods look unused, because the usage report and renaming read the declarations above instead. [Configuration as code](../docs/configuration_as_code.md) relies on them. Don't remove or narrow them when you change how references are handled, and check that an export still writes handles and an import still accepts them if a storage format moves to ids.
