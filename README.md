# BeEdit

BeEdit is an editor that allow users to create, edit and run Behat
feature files directly from the Drupal web application.

Behat and the Gherkin language, which feature files are written in, is
meant to be easy for non-technical users to write automated tests. 
However, the process for creating feature files is (until now) anything
but easy for your average, non-technical process owner. 

Feature files are stored and run on the Drupal server. So, a process
owner wishing to write features would usually have to do one of the 
following: 

* Install a Drupal environment on their own laptop and then use a
  text editor to create the feature files while executing them using
  a command line console.
* SSH into a remote server and use an editor like vi to create their 
  feature files and run them using the command line.
* Or, write the features in Microsoft Word, email the file to a
  developer who would then copy them into the Behat features folder.
  A slightly improved variation might have the process owner writing
  the features on Google Docs and sharing the link with the developer.
     
The first two options are tasks that even developers sometime have 
trouble with. So, it's unreasonable to expect non-technical users to do.
The last option, which is likely the most common, is a poor solution
since it require synchronizing files on two different platforms and
process owners aren't able to get immediate feedback on the features
they are writing.

With BeEdit, the process is much easier. Users can log into their Drupal
site, create features in a manner similar to how they would create node
content and run their tests directly from the web application.

## Server Prerequisites
* A working Behat environment is already installed on the server and
  you are able to run Behat tests using the command line.
* You have a Behat project folder which contains a behat.yml file and
  a features subdirectory.
* The Behat Drupal Extension is installed and working. While this is
  technically optional, BeEdit would be of limited use for process 
  owners if they cannot take advantage of the pre-defined step 
  definitions provided by the Drupal Extension. This link
  https://behat-drupal-extension.readthedocs.io/en/3.1/ provide
  intructions on how to install Behat and the Drupal Extensions.
* Optional - a Selenium server running in headless mode (or PhantomJS)
  if you have features that are tagged with @javascript.
   
## Installation

1. Place the BeEdit module folder in the usual location
   i.e. <drupal_8_root>/modules/contrib and enable it.
1. Go to admin/config/development/beedit/settings and set the path
   to where the Behat executable bin file and your Behat project folder
   are located. For the Behat bin file, it would typically be in 
   something like: /usr/local/bin/behat. For the Behat project 
   folder, it would often (but doesn't have to) be inside your Drupal 
   site folder. For example: /var/www/myproject/sites/default/behat
1. Set BeEdit permissions in http://site_domain/admin/people/permissions.
   There are three permission levels for BeEdit:
     1. Administer - which allows access to the BeEdit settings page.
     1. Maintain - which allows a user to create, edit and delete
      features.
     1. and View - which allows a user to view and execute features
1. Set the permission on your Behat project folder to be writeable
   by Apache or Nginx. If you are using Ubuntu, you could enter 
   something like the following: 
   chown -R www-data:www-data /var/www/mysite/sites/default/behat
   
## Using BeEdit
It is very straightforward to use BeEdit. Just go to the main page at
http://site_domain/admin/config/development/beedit and it should be
obvious how you go about creating and maintaining Behat feature files.
However, there are a few things to keep in mind:

* BeEdit does not create any content such as nodes or taxonomy terms on
  the system as they may affect the outcome of your tests. The only
  exception would be the configurations found in the settings page.
* When you create a feature, it is stored directly as a 
  file within the Behat features folder. Conversely, feature files
  can be created directly on the filesystem (i.e. using vi) as long as
  the file is write-accessible by the Apache or Nginx process. In this
  sense, BeEdit acts more like a file browser rather than a content
  store.  
* Process owners can write anything in the Feature File Content field. 
  This was done intentionally so scenarios with undefined step 
  definitions can still be created and executed. I found some of the 
  other Drupal 7 Behat editors tried to force users to just use the 
  available step definitions which I found to be too restrictive.
* On the feature create and edit forms, users can:
    * Filter on existing step definitions as provided by the Drupal
      Extension as well as those custom created in FeatureContext.php.
    * Click on a step definitions or template outline and 
      copy it to their clipboard for pasting to the feature content
      field.
    * Adjust the width of the feature content panel (left side) and
      help panel (right side) by clicking and dragging the left panel
      border.
* When creating a new folder, you can use a forward slash to create a
  a sub-folder. 
* Feature names cannot have two consecutive hyphens (ie. my--folder). 
  But multiple single hyphens are OK (ie. my-test-feature)
* It's not possible to delete a folder in BeEdit yet. You'll have to
  delete it from the command line. I'm working on a better solution.
