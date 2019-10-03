<nav id="wsformdocs-menu" hidden>
    <div class="nav-header">
        <a href="%back%" class="brand">
            <p><img src="%url%" /><br>%version%</p>
        </a>
        <button class="toggle-bar">
            <span class="fa fa-bars"></span>
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
            <a href="%index%"> Overview</a>
        </li>
    </ul>
</nav>

<script>

function initializeMenu() {
    $.getScript( '%%wsformpurl%%modules/coreNav/coreNavigation-1.1.3.js' ).done( function () {
        $('#wsformdocs-menu').coreNavigation({
            responsideSlide: true, // true or false
            dropdownEvent: "hover",
            menuPosition: "left",
            container: true
        });
    } )
}



</script>