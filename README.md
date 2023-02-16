# CreateItMigrateLister
ProcessWire Module to allow import and export of listers in Lister Pro module.

## Requirements

CreateItMigrateLister requires a ProcessWire installation with installed and licensed copy of ProcessPageListerPro
[https://processwire.com/store/lister-pro/ Lister Pro]

## How to use

The module adds an additional expandable field in the configuration screen of listers with JSON code that can be copied.

It adds a textarea field in the Lister Pro module configuration screen where JSON data can be pasted. 
When the module configuration is saved, if a lister of the same name doesn't already exist, it will be created.

In addition to importing listers via the ProcessWire admin UI, it is also possible to import them via the api.

$import = wire()->modules('CreateItMigrateLister');
$import->importLister($json);

## Limitations

No checks are done to ensure that fields included in the lister actually exist on the destination installation of ProccessWire.
In addition, lister configurations store templates and parents as numeric ids rather than names, so before importing a lister on a different installation of ProccessWire, you should check to ensure template and parent ids match, or manually update the JSON code with the correct values.
