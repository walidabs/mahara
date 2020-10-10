<div class="goals-wrapper">
    {if !$hidetitle}
    <h2 class="resumeh3">
        {str tag='mygoals' section='artefact.resume'}
        {if $controls}
        {contextualhelp plugintype='artefact' pluginname='resume' section='mygoals'}
        {/if}
    </h2>{/if}
    <div id="goalslist{$suffix}" class="card-items card-items-no-margin js-masonry" data-masonry-options='{ "itemSelector": ".card" }'>
        {foreach from=$goals item=n, name='default'}
        <div class="card">
            <h3 class="card-header has-link">
                {if $n->exists}
                    <a id="goals_edit_{$n->artefacttype}" href="{$WWWROOT}artefact/resume/editgoalsandskills.php?id={$n->id}" title="{str tag=edit$n->artefacttype section=artefact.resume}">
                    {str tag=$n->artefacttype section='artefact.resume'}
                    <span class="icon icon-pencil-alt float-right" role="presentation" aria-hidden="true"></span>
                    <span class="sr-only">{str tag=edit}</span>
                    </a>
                {else}
                    <a id="goals_edit_{$n->artefacttype}" href="{$WWWROOT}artefact/resume/editgoalsandskills.php?type={$n->artefacttype}" title="{str tag=edit$n->artefacttype section=artefact.resume}">
                          {str tag=$n->artefacttype section='artefact.resume'}
                    <span class="icon icon-pencil-alt float-right" role="presentation" aria-hidden="true"></span>
                    <span class="sr-only">{str tag=edit}</span>
                    </a>
                {/if}
            </h3>
            <div class="card-body">
                {if $n->description != ''}
                {$n->description|clean_html|safe}
                {else}
                <p class="no-results-small">
                    {str tag=nodescription section=artefact.resume}
                </p>
                {/if}
            </div>
            {if $n->files}
            <div id="resume_{$n->id}" class="has-attachment">
                <a class="collapsible collapsed card-footer" aria-expanded="false" href="#attach_goal_{$.foreach.default.index}" data-toggle="collapse">
                    <p class="text-left">
                        <span class="icon left icon-paperclip" role="presentation" aria-hidden="true"></span>
                        <span class="text-small">{str tag=attachedfiles section=artefact.blog}</span>
                        <span class="metadata">({$n->count})</span>
                        <span class="icon icon-chevron-down float-right collapse-indicator" role="presentation" aria-hidden="true"></span>
                    </p>
                </a>
                <div id="attach_goal_{$.foreach.default.index}" class="collapse">
                    <ul class="list-unstyled list-group">
                    {foreach from=$n->files item=file}
                        <li class="list-group-item">
                            <span class="file-icon-link">
                            {if $file->icon}
                                <img src="{$file->icon}" alt="" class="file-icon">
                            {else}
                                <span class="icon icon-{$file->artefacttype} icon-lg text-default file-icon" role="presentation" aria-hidden="true"></span>
                            {/if}
                            </span>
                            <span class="title">
                                <span class="text-small">{$file->title|truncate:40}</span>
                            </span>
                            <a href="{$WWWROOT}artefact/file/download.php?file={$file->attachment}">
                                <span class="icon icon-download icon-lg float-right text-watermark icon-action" role="presentation" aria-hidden="true" data-toggle="tooltip" title="{str tag=downloadfilesize section=artefact.file arg1=$file->title arg2=$file->size|display_size}"></span>
                            </a>
                        {if $file->description}
                            <div class="file-description text-small text-midtone">
                                {$file->description|clean_html|safe}
                            </div>
                        {/if}
                        </li>
                    {/foreach}
                    </ul>
                </div>
            </div>
            {/if}
            </div>
        {/foreach}
    </div>
    {if $license}
    <div class="license">
        {$license|safe}
    </div>
    {/if}
</div>
