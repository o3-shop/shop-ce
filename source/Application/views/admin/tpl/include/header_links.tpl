[{assign var="oConfig" value=$oViewConf->getConfig()}]
<ul>
    [{block name="admin_header_links"}]
    <li class="act">
        <a href="[{$oViewConf->getSelfLink()}]&cl=navigation&item=home.tpl" id="homelink" target="basefrm" class="rc">
            <b>
                <i class="fa-fw fa-solid fa-table-columns"></i>
                [{oxmultilang ident="NAVIGATION_HOME"}]
            </b>
        </a>
    </li>
    <li class="sep">
        <a href="[{$oConfig->getShopURL()}]" id="shopfrontlink" target="_blank" class="rc">
            <b>
                <i class="fa-fw fa-solid fa-home"></i>
                [{oxmultilang ident="NAVIGATION_SHOPFRONT"}]
            </b>
        </a>
    </li>
    <li class="sep">
        <a href="[{$oViewConf->getSelfLink()}]&cl=navigation&fnc=logout" id="logoutlink" target="_parent" class="rc">
            <b>
                <i class="fa-fw fa-solid fa-right-from-bracket"></i>
                [{oxmultilang ident="NAVIGATION_LOGOUT"}]
            </b>
        </a>
    </li>
    [{/block}]
</ul>
