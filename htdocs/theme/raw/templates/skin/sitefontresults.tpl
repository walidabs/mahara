            {foreach from=$sitefonts item=font}
                <div class="{cycle values='r0,r1'} listrow list-group-item flexbox">
                    <div class="col-md-9" style="font-family:'{$font.title|escape_css_string}';font-size:{$size}pt;line-height:{$size}pt;padding:3px 0;">
                        <h2 class="title">{$font.title}</h2>
                        {if $preview == 10}
                            {$font.title}
                        {/if}
                        {if $preview >= 11}
                            {str tag="sampletext$preview" section="skin"}
                        {/if}
                    </div>
                    <ul class="actionlist list-unstyled user-action-list text-small col-md-3">
                        <li class="notbtn">
                            <strong>{str tag="fonttype.$font.fonttype" section="skin"}</strong>
                        </li>
                        {if $font.fonttype == 'google'}
                            <li>
                                <a class="btn-display" href="{$WWWROOT}admin/site/font/specimen.php?font={$font.urlencode}">
                                    <span class="icon icon-tv left" role="presentation" aria-hidden="true"></span>
                                    {str tag="viewfontspecimen" section="skin"}
                                    <span class="accessible-hidden sr-only">
                                        {str tag=viewfontspecimenfor section=skin arg1=$font.title}
                                    </span>
                                </a>
                            </li>
                        {else}
                            <li>
                                <a class="btn-display" href="{$WWWROOT}admin/site/font/specimen.php?font={$font.name}">
                                    <span class="icon icon-tv left" role="presentation" aria-hidden="true"></span>
                                    {str tag="viewfontspecimen" section="skin"}
                                    <span class="accessible-hidden sr-only">
                                        {str tag=viewfontspecimenfor section=skin arg1=$font.title}
                                    </span>
                                </a>
                            </li>
                        {/if}
                        {if $font.fonttype == 'site'}
                            <li>
                                <a class="btn-edit" href="{$WWWROOT}admin/site/font/edit.php?font={$font.name}">
                                    <span class="icon icon-pencil-alt left" role="presentation" aria-hidden="true"></span>
                                    {str tag="editproperties" section="skin"}
                                    <span class="accessible-hidden sr-only">
                                        {str tag=viewfontspecimenfor section=skin arg1=$font.title}
                                    </span>
                                </a>
                            </li>
                        {/if}
                        {if $font.fonttype == 'site'}
                            <li>
                                <a class="btn-add" href="{$WWWROOT}admin/site/font/add.php?font={$font.name}">
                                    <span class="icon icon-plus left" role="presentation" aria-hidden="true"></span>
                                    {str tag="addfontvariant" section="skin"}
                                    <span class="accessible-hidden sr-only">
                                        {str tag=viewfontspecimenfor section=skin arg1=$font.title}
                                    </span>
                                </a>
                            </li>
                        {/if}
                        <li>
                            <a class="btn-del text-danger" href="{$WWWROOT}admin/site/font/delete.php?font={$font.name}">
                                <span class="icon icon-trash-alt left" role="presentation" aria-hidden="true"></span>
                                {str tag=deletefont section=skin}
                                <span class="accessible-hidden sr-only">
                                    {$font.title}
                                </span>
                            </a>
                        </li>
                    </ul>
                    <div class="clearfix"></div>
                </div>
            {/foreach}
