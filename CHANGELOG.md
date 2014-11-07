##### 0.2.0 - November 7 2014

 * Modified file filters to work on the path's basename.
 * The default inclusion filter has been changed to require files that start with
   `test_`
 * Added a filename `exclude` filter.
 * Renamed `filter` to `include` for symmetry with `exclude`.
 * Restructured test folder to establish a better default folder structure.
   One should be able to point `bin/mat test` at their test folder and not
   encounter other cruft like fixtures. I had considered making the exlusion
   process operate on a default folder set but I'm not ready to dictate that yet.
 * Putting the DSL methods in the `Matura\Tests` namespace. I was never comfortable
   with the global methods - especially because their exceedingly collision prone
   names.
