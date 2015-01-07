SlidePanel
==========

A quick and easy way to add a contextual ajax sliding panel to your site.
Created and maintained by [William Golden](http://twitter.com/egdelwonk)



For a demo goto http://codebomber.com/jquery/slidepanel

#Quickstart

##Include required files

    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.9.0/jquery.min.js"></script>
    <script type="text/javascript" src="js/jquery.slidepanel.js"></script>
    <link rel="stylesheet" type="text/css" href="css/jquery.slidepanel.css">


##Add a panel trigger
The href attribute is used to load external HTML content into your panel
    
    <a href="external.html" data-slidepanel="panel">Show Panel</a> or
    <a href="external2.html" class="panel">Show Panel</a>


##Load the plugin on your element 
    <script type="text/javascript">
          $(document).ready(function(){
              $('[data-slidepanel]').slidepanel({
                  orientation: 'top',
                  mode: 'push'
              });
          });
    </script>
    
##Options

orientaton: left (default), top, right, bottom
mode: push (default), overlay
static: true (default), false
    
Orientation sets the orientation of the rendered panel (top, right, bottom, left).  Default is left.

Mode sets the animation mode for the panel. Push, will move the panel into view and push the document body out of the way.
Overlay will animate the panel over the the document body.

If you have static content to render, set static: true.  If you are rendering ajax content, leave out,
or set to false.  Default is false.
