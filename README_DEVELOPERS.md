# WSForm

WSForm is an enhanced HTML5 rendering engine

## History

WSForm started as a relatively small one file script created for a specific customer on a specific website.
After it became a rather large feature packed extension and as we planned to make it available for the MediaWiki community 
we started to organize the file structure, classes and functions. There still is significant work there to be done.

Since we are a commercial company and customer projects always have priority, we decided to release the extension to the community now 
even though I would rather have hoped to have more time to release a good cleaned-up version that will work on any MediaWiki site.
There are might even still be files in this extension that are currently not used.
 
## Contributing
If you wish to contribute to WSForm, please read the following notes.

#### Adding POST handling function
You can add an option ```extension``` to the wsform declaration. There is information about this in the Manual of WSForm.

#### Contributing code to the WSForm core
Please fork from bitbucket ( https://bitbucket.org/wikibasesolutions/mw-wsform/ ).
Create your own personal branch with your name depending on your type of contribution.
We have 3 branched you can use : Bugfix, Feature and Hotfix. 
So you contribution would most preferable be something like : Feature/Antonio or Feature/NewTypeField

#### Contributing to the Documentation
You can contribute to the documentation. There will be a much easier way to create or edit documentation especially if 
we talk about different languages. For now the documentation is english. Contributing can be done by editing  
specials/SpecialWSForm.php. At the top of the file change public ```$allowEditDocs = false;``` to ```public $allowEditDocs = true;```

This will allow you to create new Documentation and/or Edit existing including full WYSISYG editor. Just go to 
Special:WSForm/Docs.

#### Sharing Forms
We have a FormBuilder. It's a full drag and drop builder, but the developer needs to add some documentation and some 
styling issues need to be dealt with. If it's done, we will release it and it will show up on de Special:WSForm/Docs menu.
The FormBuilder also allows you to save form layouts and share them with others.

#### Issues
Please report issues on https://bitbucket.org/wikibasesolutions/mw-wsform/.

#Final note
Discord invite : https://discord.gg/ehFrPmT if you want to ask questions


