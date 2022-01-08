<nav id="wsformdocs-menu" hidden>
    <div class="nav-header">
        %version%
        <button class="toggle-bar">
            <span>MENU</span>
        </button>
    </div>
    <ul class="menu">
        <li class="dropdown">
            <a href="#">
                <i data-feather="list"></i> Documentation
            </a>
            <ul class="dropdown-menu nfo">
                %items%
            </ul>
        </li>
        <li class="dropdown">
            <a href="#">
                <i data-feather="list"></i> Examples
            </a>
            <ul class="dropdown-menu nfo">
                %eitems%
            </ul>
        </li>
        <li class="dropdown">
            <a href="#">
                <i data-feather="list"></i> New
            </a>
            <ul class="dropdown-menu nfo">
                %new%
            </ul>
        </li>
        <li>
            %%search%%
        </li>
        <li>
            <a href="%index%"> Overview</a>
        </li>
        %changelog%
        %fb%
    </ul>
</nav>

<div id="openSearch" class="modalDialog">
    <div>	<a href="#close" title="Close" class="close">X</a>

        <h2>Search WSForm documentation</h2>
        <form class="ws-documentation" >
            <input type="text" name="searcher" id="wsform-search" placeholder="Search here.."">
        </form>
        <div id="wsform-search-results"></div>
    </div>
</div>

<script>

    function initializeMenu() {
        $.getScript( '%%wsformpurl%%Modules/coreNav/coreNavigation-1.1.3.js' ).done( function () {
            $('#wsformdocs-menu').coreNavigation({
                responsideSlide: false, // true or false
                dropdownEvent: "accordion",
                menuPosition: "left",
                container: true,
                animated: true
            });
        } );
      $('#wsform-search').on("input", function(e) {

        if( $(this).val().length > 2 ) {
          var api = new mw.Api();
          api.get({
            action: 'wsform',
            format: 'json',
            what: 'searchdocs',
            titleStartsWith: 'bs',
            for : $(this).val()
          }).done( function( data ){
            var html = '';
            $(data.wsform.result).each(function( index, val) {
                html = html + '<p><a href="' + val.link + '"><strong>' + val.name + ' (' + val.type + ')</strong></a><br>';
                html = html + '<span class="wsform-search-snippet">' + val.snippet.replace(/(<([^>]+)>)/ig,"") + '</span></p>';
            });
            $('#wsform-search-results').html( html );
          });
        }
      });
    }

</script>