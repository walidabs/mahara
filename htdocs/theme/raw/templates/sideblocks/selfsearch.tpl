<div class="card">
    <div class="card-body">
        <form id="selfsearch" class="selfsearch input-group" method="post" action="{$WWWROOT}selfsearch.php">
            <label for="sidebar-search" class="sr-only">{str tag="selfsearch"}</label>
            <input id="sidebar-search" type="text" name="query" class="form-control float-left" placeholder="{str tag='selfsearch'}">
            <span class="input-group-append">
                <button type="submit" class="btn btn-secondary"><span class="icon icon-search" role="presentation" aria-hidden="true"></span><span class="sr-only">{str tag="go"}</span></button>
            </span>
        </form>
    </div>
</div>
