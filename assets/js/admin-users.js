"use strict";
/**
 * UsersCustomListTable
 *
 * Initialize datatables and create the search / filter widget on the WP Users's table.
 *
 * @package  TPC CRM
 * @author   Jon Falcon <darkutubuki143@gmail.com>
 * @version  0.1.8
 */
var $hideToggleClass = '.hide-column-tog-crm';
var wplt;
var resetFilterHooks = Array();
var usersCustomListTable = function( $ ) {

    /**
     * Loading elements/objects for index search.
     */
    var dataLoaded = false;
    var dataRows = [];
    var dataSource = [];
    var dataFilterResults = [];
    var dataContainer = {
        sEcho: 0,
        iTotalRecords: 0,
        iTotalDisplayRecords: 0,
        aaData: [],
        hidden: [],
        extras: {
            total_records: 0,
            total_items: 0,
            number: 0,
            offset: 0
        }
    };
    var dataIndex = [];

    var fnApplyFilters = function() {

        if (dataSource.length > 0)
        {
            window.is_filtered = 0;
            var results = dataIndex.filter(function (user) {
                var checked = false;
                var filtered_fields = 0;
                var fields_passed = 0;
                var user_dt = user.date_registered.split('-');
                var registered_date = new Date(parseInt(user_dt[2]), parseInt(user_dt[0])-1, parseInt(user_dt[1]));


                //Singular filter fields:
                //Search:
                var search = $('.search-input input[type="search"]').val();
                if ((!!search) && (typeof search !== 'undefined'))
                {
                    checked = true;
                    filtered_fields++;
                    if (user.index.indexOf(search.toLowerCase()) > -1) fields_passed++;
                }

                //Not between dates:
                var not_date_from = $('.filter-inputs input[name="not-between-dates[from]"]').val();
                var not_date_to = $('.filter-inputs input[name="not-between-dates[to]"]').val();
                if (((!!not_date_from) && (typeof not_date_from !== 'undefined')) && ((!!not_date_to) && (typeof not_date_to !== 'undefined')))
                {
                    checked = true;
                    filtered_fields++;

                    var not_from_dt = not_date_from.split('/');
                    var not_from = new Date(not_from_dt[2], parseInt(not_from_dt[0])-1, not_from_dt[1]);

                    var not_to_dt = not_date_to.split('/');
                    var not_to = new Date(not_to_dt[2], parseInt(not_to_dt[0])-1, not_to_dt[1]);

                    if (registered_date <= not_from || registered_date >= not_to) fields_passed++;
                }
                //Between dates:
                var date_from = $('.filter-inputs input[name="between-dates[from]"]').val();
                var date_to = $('.filter-inputs input[name="between-dates[to]"]').val();
                if (((!!date_from) && (typeof date_from !== 'undefined')) && ((!!date_to) && (typeof date_to !== 'undefined')))
                {
                    checked = true;
                    filtered_fields++;

                    var from_dt = date_from.split('/');
                    var from = new Date(from_dt[2], parseInt(from_dt[0])-1, from_dt[1]);

                    var to_dt = date_to.split('/');
                    var to = new Date(to_dt[2], parseInt(to_dt[0])-1, to_dt[1]);

                    if (registered_date > from && registered_date < to) fields_passed++;
                }


                //Not before date:
                var not_before_date = $('.filter-inputs input[name="not-before-date[value]"]').val();
                if ((!!not_before_date) && (typeof not_before_date !== 'undefined'))
                {
                    checked = true;
                    filtered_fields++;

                    var not_before_dt = not_before_date.split('/');
                    var not_before = new Date(not_before_dt[2], parseInt(not_before_dt[0])-1, not_before_dt[1]);

                    if (registered_date >= not_before) fields_passed++;
                }

                //Before date:
                var before_date = $('.filter-inputs input[name="before-date[value]"]').val();
                if ((!!before_date) && (typeof before_date !== 'undefined'))
                {
                    checked = true;
                    filtered_fields++;

                    var before_dt = before_date.split('/');
                    var before = new Date(before_dt[2], parseInt(before_dt[0])-1, before_dt[1]);

                    if (registered_date < before) fields_passed++;
                }

                //Not after date:
                var not_after_date = $('.filter-inputs input[name="not-after-date[value]"]').val();
                if ((!!not_after_date) && (typeof not_after_date !== 'undefined'))
                {
                    checked = true;
                    filtered_fields++;

                    var not_after_dt = not_after_date.split('/');
                    var not_after = new Date(not_after_dt[2], parseInt(not_after_dt[0])-1, not_after_dt[1]);

                    if (registered_date <= not_after) fields_passed++;
                }

                //After date:
                var after_date = $('.filter-inputs input[name="after-date[value]"]').val();
                if ((!!after_date) && (typeof after_date !== 'undefined'))
                {
                    checked = true;
                    filtered_fields++;

                    var after_dt = after_date.split('/');
                    var after = new Date(after_dt[2], parseInt(after_dt[0])-1, after_dt[1]);

                    if (registered_date > after) fields_passed++;
                }


                //Multiple filter fields:
                //Not lesser than:
                $('.filter-inputs input[name="not-lesser-than[value][]"]').each(function() {
                    var parent = $(this).closest( '.filter-inputs' );
                    var field_choosen = parent.find( '.chosen-container' ).first().find( '.chosen-single span' ).html();

                    var value = $(this).val();
                    if ((!!value) && (typeof value !== 'undefined'))
                    {
                        checked = true;
                        filtered_fields++;

                        switch (field_choosen)
                        {
                            case "Posts":
                                if (parseInt(user.posts) >= parseInt(value)) fields_passed++;
                                break;
                            case "Date Registered":
                                var dt = value.split('/');
                                if ((typeof dt[0] !== 'undefined') && (typeof dt[1] !== 'undefined') && (typeof dt[2] !== 'undefined'))
                                {
                                    var lesser_date = new Date(dt[2], parseInt(dt[0])-1, dt[1]);
                                    if (registered_date >= lesser_date) fields_passed++;
                                }else{
                                    //We exclude invalid values for filtering search results,
                                    //therefore, we decrement back the filtered_fields variable to its
                                    //previous count.
                                    filtered_fields--;
                                }
                                break;
                            default:
                                break;
                        }
                    }
                });

                //Lesser than:
                $('.filter-inputs input[name="lesser-than[value][]"]').each(function() {
                    var parent = $(this).closest( '.filter-inputs' );
                    var field_choosen = parent.find( '.chosen-container' ).first().find( '.chosen-single span' ).html();

                    var value = $(this).val();
                    if ((!!value) && (typeof value !== 'undefined'))
                    {
                        checked = true;
                        filtered_fields++;

                        switch (field_choosen)
                        {
                            case "Posts":
                                if (parseInt(user.posts) < parseInt(value)) fields_passed++;
                                break;
                            case "Date Registered":
                                var dt = value.split('/');
                                if ((typeof dt[0] !== 'undefined') && (typeof dt[1] !== 'undefined') && (typeof dt[2] !== 'undefined'))
                                {
                                    var lesser_date = new Date(dt[2], parseInt(dt[0])-1, dt[1]);
                                    if (registered_date < lesser_date) fields_passed++;
                                }else{
                                    //We exclude invalid values for filtering search results,
                                    //therefore, we decrement back the filtered_fields variable to its
                                    //previous count.
                                    filtered_fields--;
                                }
                                break;
                            default:
                                break;
                        }
                    }
                });

                //Not greater than:
                $('.filter-inputs input[name="not-greater-than[value][]"]').each(function() {
                    var parent = $(this).closest( '.filter-inputs' );
                    var field_choosen = parent.find( '.chosen-container' ).first().find( '.chosen-single span' ).html();
                    var default_field = parent.find( '.chosen-container' ).first().find( '.chosen-single span' ).html().toLowerCase();
                    var default_field_chosen = default_field.replace(/ /ig, "_");
                    var value = $(this).val();
                    if ((!!value) && (typeof value !== 'undefined'))
                    {
                        checked = true;
                        filtered_fields++;

                        switch (field_choosen)
                        {
                            case "Posts":
                                if (parseInt(user.posts) <= parseInt(value)) fields_passed++;
                                break;
                            case "Date Registered":
                                var dt = value.split('/');
                                if ((typeof dt[0] !== 'undefined') && (typeof dt[1] !== 'undefined') && (typeof dt[2] !== 'undefined'))
                                {
                                    var greater_date = new Date(dt[2], parseInt(dt[0])-1, dt[1]);
                                    if (registered_date <= greater_date) fields_passed++;
                                }else{
                                    //We exclude invalid values for filtering search results,
                                    //therefore, we decrement back the filtered_fields variable to its
                                    //previous count.
                                    filtered_fields--;
                                }
                                break;
                            default:
                                if(typeof default_field_chosen !== 'undefined')
                                // console.log(typeof default_field_chosen);
                                    break;
                        }
                    }
                });

                //Greater than:
                $('.filter-inputs input[name="greater-than[value][]"]').each(function() {
                    var parent = $(this).closest( '.filter-inputs' );
                    var field_choosen = parent.find( '.chosen-container' ).first().find( '.chosen-single span' ).html();
                    var default_field = parent.find( '.chosen-container' ).first().find( '.chosen-single span' ).html().toLowerCase();
                    var default_field_chosen = default_field.replace(/ /ig, "_");
                    var value = $(this).val();
                    if ((!!value) && (typeof value !== 'undefined'))
                    {
                        checked = true;
                        filtered_fields++;

                        switch (field_choosen)
                        {
                            case "Posts":
                                if (parseInt(user.posts) > parseInt(value)) fields_passed++;
                                break;
                            case "Date Registered":
                                var dt = value.split('/');
                                if ((typeof dt[0] !== 'undefined') && (typeof dt[1] !== 'undefined') && (typeof dt[2] !== 'undefined'))
                                {
                                    var greater_date = new Date(dt[2], parseInt(dt[0])-1, dt[1]);
                                    if (registered_date > greater_date) fields_passed++;
                                }else{
                                    //We exclude invalid values for filtering search results,
                                    //therefore, we decrement back the filtered_fields variable to its
                                    //previous count.
                                    filtered_fields--;
                                }
                                break;
                            default:
                                if(typeof default_field_chosen !== 'undefined')
                                // console.log(typeof default_field_chosen);
                                    break;
                        }
                    }
                });

                //Not equal:
                $('.filter-inputs select[name="not-equals[value][]"]').each(function() {
                    var parent = $(this).closest( '.filter-inputs' );
                    var field_choosen = parent.find( '.chosen-container' ).first().find( '.chosen-single span' ).html();
                    var default_field = parent.find( '.chosen-container' ).first().find( '.chosen-single span' ).html().toLowerCase();
                    var default_field_chosen = default_field.replace(/ /ig, "_");
                    var value = $(this).val();
                    if( (!!value) && (typeof value !== 'undefined' ) ) {

                        checked = true;
                        filtered_fields++;
                        switch (field_choosen) {

                            case "Username":
                                if (user.login !== value.toLowerCase()) fields_passed++;
                                break;
                            case "Name":
                                if ( user.html[1] !== value ) fields_passed++;
                                break;
                            case "Email":
                                if (user.email !== value.toLowerCase()) fields_passed++;
                                break;
                            case "Role":
                                if (user.roles.indexOf(value.toLowerCase()) === -1) fields_passed++;
                                break;
                            case "Posts":
                                if (parseInt(user.posts) !== parseInt(value.toLowerCase())) fields_passed++;
                                break;
                            case "Date Registered":
                                var dt = value.split('/');
                                if ((typeof dt[0] !== 'undefined') && (typeof dt[1] !== 'undefined') && (typeof dt[2] !== 'undefined'))
                                {
                                    var entered_date = new Date(dt[2], parseInt(dt[0])-1, dt[1]);
                                    if (registered_date !== entered_date) fields_passed++;
                                }else{
                                    //We exclude invalid values for filtering search results,
                                    //therefore, we decrement back the filtered_fields variable to its
                                    //previous count.
                                    filtered_fields--;
                                }
                                break;
                            default:
                                if(user[default_field_chosen].indexOf(value) === -1) fields_passed++;
                                break;
                        }
                    }
                });

                //Equals:
                $('.filter-inputs select[name="equals[value][]"]').each(function() {
                    var parent = $(this).closest( '.filter-inputs' );
                    var field_choosen = parent.find( '.chosen-container' ).first().find( '.chosen-single span' ).html();
                    var default_field = parent.find( '.chosen-container' ).first().find( '.chosen-single span' ).html().toLowerCase();
                    var default_field_chosen = default_field.replace(/ /ig, "_");
                    //console.log(default_field_chosen);
                    var value = $(this).val();
                    //console.log(user);
                    if ((!!value) && (typeof value !== 'undefined'))
                    {
                        checked = true;
                        filtered_fields++;
                        //console.log(user);
                        switch (field_choosen)
                        {
                            case "Username":
                                if (user.login === value.toLowerCase()) fields_passed++;
                                break;
                            case "Name":
                                if ( user.html[1] === value) fields_passed++;
                                break;
                            case "Email":
                                if (user.email === value.toLowerCase()) fields_passed++;
                                break;
                            case "Role":
                                if (user.roles.indexOf(value.toLowerCase()) > -1) fields_passed++;
                                break;
                            case "Posts":
                                if (parseInt(user.posts) === parseInt(value.toLowerCase())) fields_passed++;
                                break;
                            case "Date Registered":
                                var dt = value.split('/');
                                if ((typeof dt[0] !== 'undefined') && (typeof dt[1] !== 'undefined') && (typeof dt[2] !== 'undefined'))
                                {
                                    var entered_date = new Date(dt[2], parseInt(dt[0])-1, dt[1]);
                                    if (registered_date === entered_date) fields_passed++;
                                }else{
                                    //We exclude invalid values for filtering search results,
                                    //therefore, we decrement back the filtered_fields variable to its
                                    //previous count.
                                    filtered_fields--;
                                }
                                break;
                            default:
                                //console.log(user[default_field_chosen]);
                                if(user[default_field_chosen].indexOf(value) > -1) fields_passed++;
                                break;
                        }


                    }
                });

                //Does not contain:
                $('.filter-inputs input[name="not-contains[value][]"]').each(function() {
                    var value = $(this).val();
                    if ((!!value) && (typeof value !== 'undefined'))
                    {
                        checked = true;
                        filtered_fields++;
                        if (user.index.indexOf(value.toLowerCase()) == -1) fields_passed++;
                    }
                });

                //Contains:
                $('.filter-inputs input[name="contains[value][]"]').each(function() {
                    var value = $(this).val();
                    if ((!!value) && (typeof value !== 'undefined'))
                    {
                        checked = true;
                        filtered_fields++;
                        if (user.index.indexOf(value.toLowerCase()) > -1) fields_passed++;
                    }
                });

                if (checked) window.is_filtered++;
                return ((filtered_fields === fields_passed) && (checked));
            });


            var newDataRows = [];
            for(var i=0; i<results.length; i++)
                newDataRows[i] = results[i].html;

            dataRows = dataSource;
            if ((results.length > 0) || (window.is_filtered > 0))
            {
                dataRows = newDataRows;
                dataContainer.sEcho = 0;
                dataContainer.iTotalRecords = results.length;
                dataContainer.iTotalDisplayRecords = results.length;
                dataContainer.extras.total_records = results.length;
                dataContainer.extras.total_items = results.length;
                dataContainer.extras.number = results.length;
                dataContainer.extras.offset = 0;
            }else{
                dataContainer.iTotalRecords = dataRows.length;
                dataContainer.iTotalDisplayRecords = dataRows.length;
            }

        }
    };


    /**
     * Pointer to the root
     * @type {object}
     */
    var $root 	 = this;

    /**
     * List of events attached to this object
     * @type {Object}
     */
    var events   = { };

    /**
     * Lists of filtered fields
     * @type {array}
     */
    var filtered = [ ];

    /**
     * List of inserted in the filters dropdown
     * @type {Object}
     */
    var filters = { };

    /**
     * Load screen
     * @type {Object}
     */
    var loadScreen;

    /**
     * Table Form
     * @type {Object}
     */
    var tableForm;

    /**
     * Initiate this object only once
     * @type {Boolean}
     */
    var instance;

    /**
     * Search buttons
     * @type {Object}
     */
    var searchButtons;

    /**
     * Cached column data
     * @type {Array}
     */
    var _cachedColumns = [];

    /**
     * Default arguments
     * @type {Object}
     */
    var defaults = {
        columns 		: { },
        filters 		: { },
        requests 		: { },
        itemRemoved		: function( item ) { },
        itemAdded		: function( item ) { },
        addingItem 		: function( item ) { },
        buildWidget 	: function( id, value ) { }
    };

    /**
     * List of custom filters callback
     * @type {Array}
     */
    var customFilters = [];

    /**
     * We need a reference to the main object
     * @type {Object}
     */
    var that;

    /**
     * Display the loading icon
     */
    var _showLoadingScreen = function( ) {
        wplt   = $( '.wp-list-table' );
        var count  = parseInt( wplt.find( 'tr:first-child th' ).length );

        if( !wplt.find( '#tpcload-column' ).length ) {
            loadScreen = $(  '<td colspan="' + count + '" id="tpcload-column"><img src="' + TPC_CRM.url + '/assets/img/wpspin_light.gif" width="16" height="16" /> Updating the table..</td>' );
            //wplt.find( 'tr').each(function() {$(this).hide();});
            wplt.prepend(loadScreen);
        }
    };

    /**
     * Hide the loading screen
     */
    var _hideLoadingScreen = function( ) {
        if( loadScreen ) {
            loadScreen.remove( );
        }
        //wplt.find( 'tr').each(function() {$(this).show();});
    };

    /**
     * Gets the table form
     * @return {object}
     */
    var _getForm = function( ) {
        if( !tableForm ) {
            tableForm = $( '.subsubsub ~ form' );
        }
        return tableForm;
    }

    /**
     * Gets the serialized form string
     * @return {string}
     */
    var _getSerializedForm = function( ) {
        var data = _getForm( ).serialize( ),
            ret  = '';

        if( data ) {
            data = data.replace( /&?action=[^&]+/, '' );
            var ret = '&' + data;
        }

        return ret;
    }

    /**
     * Show the notification div
     * @param  {string} msg
     */
    var _showNotification = function( msg, isError ) {
        isError = isError || false;
        var div = this ? this.notificationDiv : $( '.notification-div' );

        if( isError ) {
            div.removeClass( 'updated' ).addClass( 'error' );
        } else {
            div.removeClass( 'error' ).addClass( 'updated' );
        }

        div.html( '<p>' + msg + '</p>' );
        div.slideDown( 'fast', function( ) {
            div.delay( 5000 ).slideUp( 'fast' );
        } );
    }

    /**
     * Extend the Datatable to add custom template and plugins
     * @param  {Object} $this Pointer to the parent function
     */
    var _extendDataTable = function( $this ) {
        $.extend( true, $.fn.dataTable.defaults, {
            oLanguage: {
                sLengthMenu	: "_MENU_",
                sSearch		: '<div class="search-input">_INPUT_<span class="search-buttons"><button type="submit" class="user-search-button button button-primary"><i class="dashicons dashicons-search"></i></button> <button type="button" class="button button-secondary reset-users-button"><i class="glyphicon glyphicon-repeat"></i></button><a href="#" class="button button-ternary" id="toggle-filters"><i class="glyphicon glyphicon-chevron-down" id="toggle-filters-icon"></i></a></span></div>',
                sInfo		: "<strong>_START_</strong>-<strong>_END_</strong> of <strong>_TOTAL_</strong>",
                oPaginate	: {
                    sPrevious	: "&larr;",
                    sNext		: "&rarr;"
                }
            }
        } );

        $.fn.dataTableExt.oApi.fnResetAllFilters = function (oSettings, bDraw) {
            for(var iCol = 0; iCol < oSettings.aoPreSearchCols.length; iCol++) {
                oSettings.aoPreSearchCols[ iCol ].sSearch = '';
            }
            oSettings.oPreviousSearch.sSearch = '';

            if(typeof bDraw === 'undefined') bDraw = true;
            if(bDraw) this.fnDraw();
        }

        $.fn.dataTableExt.oApi.fnReloadAjax = function ( oSettings, sNewSource, fnCallback, bStandingRedraw ) {

            if ( typeof sNewSource != 'undefined' && sNewSource != null ) {
                oSettings.sAjaxSource = sNewSource;
            }
            this.oApi._fnProcessingDisplay( oSettings, true );
            var that   = this;
            var iStart = oSettings._iDisplayStart;
            var aData  = [];

            if( this.oApi._fnServerParams ) {
                this.oApi._fnServerParams( oSettings, aData );
            }

            oSettings.fnServerData( oSettings.sAjaxSource, aData, function ( json ) {

                /* Clear the old information from the table */
                that.oApi._fnClearTable( oSettings );

                /* Got the data - add it to the table */
                var aData = ( oSettings.sAjaxDataProp !== "" ) ?
                    that.oApi._fnGetObjectDataFn( oSettings.sAjaxDataProp )( json ) : json;
                aData     = aData || [];
                aData[ 'bRefreshed' ] = true;

                for ( var i = 0; i < aData.length; i++ ) {
                    that.oApi._fnAddData( oSettings, aData[ i ] );
                }

                oSettings.aiDisplay = oSettings.aiDisplayMaster.slice( );
                that.fnDraw( );

                if ( typeof bStandingRedraw != 'undefined' && bStandingRedraw === true ) {
                    oSettings._iDisplayStart = iStart;
                    that.fnDraw( false );
                }

                that.oApi._fnProcessingDisplay( oSettings, false );

                /* Callback user function - for event handlers etc */
                if ( typeof fnCallback == 'function' && fnCallback != null ) {
                    fnCallback( oSettings );
                }

            }, oSettings );

            if ( oSettings.oFeatures.bServerSide ) {
                this.fnClearTable(oSettings);
                this.fnDraw();
            }
        }
    };

    /**
     * Get the search button
     */
    var _searchButtons = function( ) {
        searchButtons = searchButtons || $( '.user-search-button' );
        if( typeof searchButtons == "object" ) {
            return searchButtons;
        }
    }

    /**
     * Instantiate the datatable on WP Users Table
     * @param {object} $this Pointer to the parent function
     */
    var _addDataTable = function( $this ) {
        /**
         * =========== Pipelining =========================
         * Pipeline the request so we won't be DDOs'd
         * ================================================
         */
        /**
         * Cached data
         * @type {Object}
         */
        var oCache = {
            iCacheLower: -1
        };

        /**
         * Sets the column data
         * @param  {array} aoData 	List of data from the server
         * @param  {string} sKey   	Column ID
         * @param  {mixed} mValue 	Column value
         */
        function fnSetKey( aoData, sKey, mValue ) {
            for ( var i=0, iLen=aoData.length ; i<iLen ; i++ )
            {
                if ( aoData[i].name == sKey )
                {
                    aoData[i].value = mValue;
                }
            }
        }

        /**
         * Gets the column data
         * @param  {array} aoData 	List of data from the server
         * @param  {string} sKey   	Column ID
         */
        function fnGetKey( aoData, sKey ) {
            for ( var i=0, iLen=aoData.length ; i<iLen ; i++ )
            {
                if ( aoData[i].name == sKey )
                {
                    return aoData[i].value;
                }
            }
            return null;
        }

        /**
         * Process the server request and pipeline it
         * @param  {string} sSource    		Url to the server script
         * @param  {array} aoData     		List of arguments passed to the server
         * @param  {function} fnCallback 	Callback function
         */
        function fnDataTablesPipeline ( sSource, aoData, fnCallback ) {
            var iPipe            = 2;
            var bNeedServer      = false;
            var sEcho            = fnGetKey( aoData, "sEcho" );
            var iRequestStart    = fnGetKey( aoData, "iDisplayStart" );
            var iRequestLength   = fnGetKey( aoData, "iDisplayLength" );
            var iRequestEnd      = iRequestStart + iRequestLength;
            oCache.iDisplayStart = iRequestStart;

            //console.log(sSource);
            //console.log(aoData);

            /* Add the serialized form data to the query */
            //sSource += _getSerializedForm( );

            /* outside pipeline? */
            if ( oCache.iCacheLower < 0 || iRequestStart < oCache.iCacheLower || iRequestEnd > oCache.iCacheUpper )
            {
                bNeedServer = true;
            }

            /* sorting etc changed? */
            if ( oCache.lastRequest && !bNeedServer )
            {
                for( var i=0, iLen=aoData.length ; i<iLen ; i++ )
                {
                    if ( aoData[i].name != "iDisplayStart" && aoData[i].name != "iDisplayLength" && aoData[i].name != "sEcho" )
                    {
                        if ( !oCache.lastRequest.length ||aoData[i].value != oCache.lastRequest[i].value )
                        {
                            bNeedServer = true;
                            break;
                        }
                    }
                }
            }

            /* Store the request for checking next time around */
            oCache.lastRequest = aoData.slice();

            bNeedServer = true;
            if ( bNeedServer ) {
                if (iRequestStart < oCache.iCacheLower) {
                    iRequestStart = iRequestStart - (iRequestLength * (iPipe - 1));
                    if (iRequestStart < 0) {
                        iRequestStart = 0;
                    }
                }

                oCache.iCacheLower = iRequestStart;
                oCache.iCacheUpper = iRequestStart + (iRequestLength * iPipe);
                oCache.iDisplayLength = fnGetKey(aoData, "iDisplayLength");
                fnSetKey(aoData, "iDisplayStart", iRequestStart);
                fnSetKey(aoData, "iDisplayLength", iRequestLength * iPipe);

                dataContainer.sEcho = sEcho;
                dataContainer.extras.number = iRequestLength;
                dataContainer.extras.offset = iRequestStart;


                if (!dataLoaded) {

                    var source = TPC_CRM.ajax + '?action=get_all_users';

                    $.ajax({
                        dataType: 'json',
                        url: source,
                        data: null,
                        async: true,
                        success: function (json) {
                            _hideLoadingScreen();

                            // Stop further processing.dt hangover
                            $this.userDataTable.trigger('keypress');
                            dataIndex = json.items;
                            dataRows = json.rows;
                            dataSource = json.rows;
                            dataContainer.iTotalRecords = json.total_items;
                            dataContainer.iTotalDisplayRecords = json.total_items;
                            dataContainer.hidden = json.hidden_columns;
                            dataContainer.extras = json.extras;
                        },
                        error: function (xhr, status) {
                            console.log(xhr);
                            if (status == 'timeout') {
                                _searchButtons().html('<i class="dashicons dashicons-search"></i>');
                            }
                        }
                    });


                    $.ajax({
                        dataType: 'json',
                        method: "POST",
                        url: sSource,
                        data: aoData,
                        async: true,
                        success: function ( json ) {
                            //console.log(json);
                            // Stop further processing.dt hangover
                            $this.userDataTable.trigger('keypress');
                            /* Callback processing */
                            oCache.lastJson = $.extend( true, {}, json );

                            if ( oCache.iCacheLower != oCache.iDisplayStart )
                            {
                                json.aaData.splice( 0, oCache.iDisplayStart - oCache.iCacheLower );
                            }

                            json.aaData.splice( oCache.iDisplayLength, json.aaData.length );
                            fnCallback( json );
                        },
                        error: function(xhr, status) {
                            console.log(xhr);
                            if (status == 'timeout') {
                                _searchButtons().html( '<i class="dashicons dashicons-search"></i>' );
                            }
                        }
                    });

                    dataLoaded = true;
                }else{

                    if (dataSource.length > 0)
                    {
                        dataContainer.aaData = dataRows.slice();
                        json = dataContainer;
                        //console.log(json);

                        if (dataContainer.extras.offset > 0) json.aaData.splice( 0, dataContainer.extras.offset );
                        json.aaData.splice( dataContainer.extras.number, json.aaData.length );

                        fnCallback( json );
                        _searchButtons().html( '<i class="dashicons dashicons-search"></i>' );
                        $('.search-input input[type="search"]').prop('disabled', false);
                    }
                }

            } else {
                var json   = $.extend(true, {}, oCache.lastJson);
                json.sEcho = sEcho; /* Update the echo for each response */
                json.aaData.splice( 0, iRequestStart-oCache.iCacheLower );
                json.aaData.splice( iRequestLength, json.aaData.length );
                fnCallback( json );
                return;
            }
        }

        /**
         * Old Custom server data callback function
         * @param  {string} sUrl       		Url to the server script
         * @param  {array} aoData     		List of data passed to the server
         * @param  {function} fnCallback 	Function called after query
         * @param  {object} oSettings  		Datatable Settings
         */
        var fnServerParams = function ( sUrl, aoData, fnCallback, oSettings ) {

            _searchButtons().html( '<img src="' + TPC_CRM.url + '/assets/img/wpspin_light.gif" width="16" height="16" />' );
            oSettings.jqXHR = $.ajax( {
                "url":  sUrl,
                "data": aoData,
                "success": function (json) {
                    if ( json.sError ) {
                        oSettings.oApi._fnLog( oSettings, 0, json.sError );
                    }

                    _searchButtons().html( '<i class="dashicons dashicons-search"></i>' );

                    $(oSettings.oInstance).trigger('xhr', [oSettings, json]);
                    fnCallback( json );
                },
                "dataType": "json",
                "cache": true,
                "type": oSettings.sServerMethod,
                "error": function (xhr, error, thrown) {
                    _searchButtons().html( '<i class="dashicons dashicons-search"></i>' );
                    if ( error == "parsererror" ) {
                        oSettings.oApi._fnLog( oSettings, 0, "DataTables warning: JSON data from "+
                            "server could not be parsed. This is caused by a JSON formatting error." );
                    }
                }
            } );
        };

        /**
         * Actions taken when the ajax request is done
         * @param  {object} e        	Event object
         * @param  {object} settings 	Datatable settings
         * @param  {object} json     	Reply from the server
         */
        var fnAjaxCall = function( e, settings, json ) {
            var aHidden = json && json.hidden ? json.hidden : [ ];
        }

        /**
         * Render the table
         */
        var fnRenderTable = function( ) {
            $( '.dataTables_paginate' ).addClass( 'tablenav' ).wrapInner( '<div class="tablenav-pages"></div>' );
            fnHiddenColumns( );

            if( oCache.lastJson ) {
                var thIndex = parseInt( oCache.lastJson.extras.sort_index );
                var order   = oCache.lastJson.extras.sort_order == 'asc' ? 'desc' : 'asc';

                if( thIndex ) {
                    thIndex += 1;
                    $this.userDataTable.find( 'th.sorted' ).removeClass( 'sorted asc desc' );
                    $this.userDataTable.find( 'tr th:nth-child(' + thIndex + ')' ).addClass( 'sorted ' + order );
                }
            }
        }

        /**
         * Display the loading column
         * @param  {Object} e          Event Object
         * @param  {Object} settings   Table Settings
         * @param  {Boolean} processing
         */
        var fnProcessing = function( e, settings, processing ) {
            if( processing ) {
                _showLoadingScreen( );
                //} else if( settings.bDrawing == false ) {
            } else {
                _hideLoadingScreen( );
            }
        }

        /**
         * Hides the hidden columns
         * @param  {object} hidden 		Hidden columns
         */
        var fnHiddenColumns = function( hidden ) {
            if( !oCache.lastJson ) return;
            var aHidden = oCache.lastJson.hidden;

            for( var i in aHidden ) {
                var hide = aHidden[ i ];
                var j    = parseInt( i ) + 1;
                var sel  = 'tr td:nth-child(' + j + ')';

                if( i ) {
                    $this.userDataTable.find( sel ).hide( );
                }
            }
        }

        var dataTableInitOptions = {
            "iDisplayLength"	: 20,
            "aLengthMenu"		: [ [ 5, 10, 20, 30, 50, 100 -1 ], [ 5, 10, 20, 30, 50, 100, "All" ] ],
            "bStateSave" 		: false,
            "bProcessing"		: true,
            "bServerSide"		: true,
            "sAjaxSource"		: TPC_CRM.ajax + '?action=draw_user_table',
            "sServerMethod": "POST",
            "fnServerData"	: fnDataTablesPipeline,
            "bDestroy"      : true
        }
        $this.userDataTable = $( '.wp-list-table' ).dataTable( dataTableInitOptions);

        $this.userDataTable.on( 'draw.dt', fnRenderTable );
        $this.userDataTable.on( 'xhr.dt', fnAjaxCall );
        $this.userDataTable.on( 'processing.dt', fnProcessing );

        /* Attach events so the table refreshes when the filters are changed. */
        _getForm().on( 'submit', function(e) {
            e.preventDefault( );
            fnApplyFilters( );
            $this.userDataTable.fnReloadAjax( );
        });

        _getForm().on( 'change', '.filter-input', function(e){
            fnApplyFilters( );
            $this.userDataTable.fnReloadAjax( );
        } );

        _getForm().on( 'keyup.input', '.filter-input', function(e){
            fnApplyFilters( );
            $this.userDataTable.fnReloadAjax( );
        } );

        _getForm().on( 'chosen:updated', function(e){
            if (dataSource.length > 0)
            {
                fnApplyFilters( );
                $this.userDataTable.fnReloadAjax( );
            }
        });

    };

    /**
     * Creates a way to hook to an event
     * @param  {string}   event    	Event ID
     * @param  {Function} callback 	Callback function
     */
    var _addEvent = function ( event, callback ) {
        if( typeof callback == "function" ) {
            if( !events.event ) {
                events[ event ] = [];
            }
            events[ event ].push( callback );
        } else {
            console.log( callback + " must be a function" );
        }
    };

    /**
     * Adds a way to fire an event
     * @param  {String} event Event ID
     */
    var _fireEvent = function( event, param1, param2, param3 ) {
        if( !events[ event ] ) return false;
        param1 = param1 || null;
        param2 = param2 || null;
        param3 = param3 || null;

        $.each( events[ event ], function( i, cb ) {
            if( param1 ) {
                cb( param1 );
            } else if ( param2 ) {
                cb( param1, param2 );
            } else if( param3 ) {
                cb( param1, param2, param3 );
            } else {
                cb();
            }
        } );
    }

    /**
     * Adds filters from a given data
     * @param {[type]} filters [description]
     */
    var _addFilters = function( filters ) {
        $.each( filters, function( i, val ) {
            switch( i ) {
                case "between-dates":
                    _checkFilter( 'between_dates' );
                    _addBetweenDatesFilter( val.field, val.from, val.to );
                    break;
                case "not-between-dates":
                    _checkFilter( 'not_between_dates' );
                    _addBetweenDatesFilter( val.field, val.form, val.to);
                    break;
                case "before-date":
                    _checkFilter( 'before_date' );
                    _addBeforeDate( val.field, val.value );
                    break;
                case "not-before-date":
                    _checkFilter( 'not_before_date' );
                    _addNotBeforeDate( val.field, val.value );
                    break;
                case "after-date":
                    _checkFilter( 'after_date' );
                    _addAfterDate( val.field, val.value );
                    break;
                case "not-after-date":
                    _checkFilter( 'not_after_date' );
                    _addNotAfterDate( val.field, val.value );
                    break;
                case "lesser-than":
                    _checkFilter( 'field_lesser_than' );
                    $.each( val, function( j, opt ) {
                        _addLesserThanField( opt.field, opt.value );
                    } );
                    break;
                case "not-lesser-than":
                    _checkFilter( 'field_not_lesser_than' );
                    $.each( val, function(j, opt) {
                        _addNotLesserThanField( opt.field, opt.value );
                    });
                    break;
                case "greater-than":
                    _checkFilter( 'field_greater_than' );
                    $.each( val, function( j, opt ) {
                        _addGreaterThanField( opt.field, opt.value );
                    } );
                    break;
                case "not-greater-than":
                    _checkFilter( 'field_not_greater_than' );
                    $.each( val, function(j, opt) {
                        _addNotGreaterThanField( opt.field, opt.value)
                    });
                    break;
                case "equals":
                    _checkFilter( 'field_equal' );
                    $.each( val, function( j, opt ) {
                        _addEqualsField( opt.field, opt.value );
                    } );
                    break;
                case "not-equals":
                    _checkFilter('field_not_equal');
                    $.each(val, function(j, opt) {
                        _addNotEqualsField(opt.field, opt.value);
                    });
                    break;
                case "contains":
                    _checkFilter( 'field_contains' );
                    $.each( val, function( j, opt ) {
                        _addContainsField( opt.field, opt.value );
                    } );
                    break;
                case "not-contains":
                    _checkFilter( 'field_not_contains' );
                    $.each( val, function( j, opt ) {
                        _addNotContainsField( opt.field, opt.value );
                    } );
                    break;
                default:
                    break;
            }
        } );
    };

    /**
     * Add the custom filter
     * @param {String} id       	Filter ID
     * @param {Function} fieldCb  	Callback function for creating the field settings
     */
    var _addCustomFilter = function( optionId, id, fieldCb ) {
        if( !that.searchFilterDropdown.find( 'option[value="' + optionId + '"]' ).length ) {
            var option = $( '<option value="' + optionId + '">' + optionId + '</option>' );
            that.searchFilterDropdown.append( option );
            that.searchFilterDropdown.trigger( "chosen:updated" );
            _createFilterField( id, fieldCb );
        }
    };

    /**
     * Remove filters
     */
    var _removeFilters = function( ) {
        $( '#filters-fields .filter-item' ).remove( );
    }

    /**
     * ================ Helpers ======================================
     * Helper functions for checking and formatting
     * ===============================================================
     */
    /**
     * Removes removable fields from the filters dropdown
     * @param  {object} $this 		Pointer to the parent function
     * @param  {string} id    		Filter id
     * @return {boolean|string} 	Returns the id, if the field can't be removed.
     */
    var _checkFilter = function ( id ) {
        if( _isRemoved(id) ) return false;

        if( _isRemovable( id ) ) {
            filtered.push( id );
            that.searchFilterDropdown.find( '[value="' + id + '"]' ).remove( );
            that.searchFilterDropdown.trigger( "chosen:updated" );
        }

        return id;
    };

    /**
     * Checks if the field is removable
     * @param  {string}  id Field ID
     * @return {Boolean}
     */
    var _isRemovable = function( id ) {
        return /(field|preset)/.test( id ) === false;
    };

    /**
     * Checks if the field is already removed
     * @param  {string}  id Field id
     * @return {Boolean}
     */
    var _isRemoved = function( id ) {
        return ( $.inArray( id, filtered ) >= 0 );
    };

    /**
     * Formats a date so we can have a unified format
     * @param  {string|object} dateStr 		Can be a string or a Date Object
     * @return {object}
     */
    var _formatDate	= function( dateStr ) {
        if( typeof dateStr !== "object" ) {
            var chunks = dateStr.split(/(\d{2})\/(\d{2})\/(\d{4})/)
            obj    = new Date( chunks[ 3, 1, 2 ] ),
                str    = dateStr;
        } else {
            var obj    = dateStr,
                str    = ( dateStr.getMonth( ) + 1 ) + '/' + dateStr.getDate( ) + '/' + dateStr.getFullYear( ),
                chunks = [ "", ( dateStr.getMonth( ) + 1 ), dateStr.getDate( ), dateStr.getFullYear( ), "" ];
        }

        return {
            obj 	: obj,
            str 	: str,
            chunks 	: chunks
        }
    };

    /**
     * Creates the filter field
     * @param  {string}   id 	Field ID
     * @param  {function} cb 	Callback function
     */
    var _createFilterField = function( id, cb ) {
        var wrap 		 = $( '<div class="filter-item clearfix"></div>' ),
            removeButton = $( '<a href="#" class="deleteFilter"><i class="dashicons dashicons-trash"></i></a>' ),
            inputWrap 	 = $( '<span class="filter-inputs"></span>' ),
            filterOpts   = cb( inputWrap );

        if( !filterOpts ) return false;

        wrap
            .append( removeButton )
            .append( filterOpts );

        if( typeof that.options.addingItem === "function" ) {
            that.options.addingItem( wrap );
        }

        wrap.appendTo( that.searchTabFilters );
        if( typeof that.options.itemAdded === "function" ) {
            that.options.itemAdded( wrap );
        }

        removeButton.on( "click", function( e ) {
            e.preventDefault();
            $( this ).parents( ".filter-item" ).slideUp( 200, function( ) {
                if( typeof that.options.itemRemoved === "function" ) {
                    that.options.itemRemoved( $( this ) );
                }

                if ( _isRemoved( id ) ) {
                    var opt;
                    for( var i in filters ) {
                        if( i == id ) {
                            opt = filters[ i ];
                        }
                    }
                    opt.prependTo( that.searchFilterDropdown );
                    that.searchFilterDropdown.trigger( "chosen:updated" );
                }

                $( this ).remove( );
                fnApplyFilters( );
                that.userDataTable.fnReloadAjax( );
            } );


        } );
    };

    /**
     * Creates the filterable columns
     * @param  {string} defaultField 	Default field Name
     * @param  {string} defaultValue 	Default field Value
     * @param  {string} format       	Data type of the field
     */
    var _createFilterableColumns = function ( defaultField, defaultValue, format, fixValue ) {
        /* Make the parameters optional */
        defaultField = defaultField || '';
        defaultValue = defaultValue || '';
        fixValue     = fixValue || false;
        format       = format || [ "string" ];
        var firstOpt = '';
        var counter  = 0;

        var fieldInput = $( '<select class="filter-input filter-input-select" name="greater-than[field][]"></select>' );

        if( fixValue ) {
            var fieldValue = $( '<select class="filter-input" name="greater-than[value][]" disabled></select>' );
        } else {
            var fieldValue = $( '<input class="filter-input" type="text" name="greater-than[value][]" disabled>' );
        }


        //This adds the values of the equals[field][] select/dropdown box.
        $.each( that.options.columns, function( i ) {

            if( i == 'cb' ) return;

            var id 	  = i,
                value = that.options.columns[ i ],
                valid = false;

            if( $.inArray( "string", format ) >= 0 && value.isString ) {
                valid = true;
            } else if ( $.inArray( "date", format ) >= 0 && value.isDate ) {
                valid = true;
            } else if( $.inArray( "number", format ) >= 0 && value.isNumeric ) {
                valid = true;
            }

            if( valid ) {
                var opt = $( '<option value="' + id + '">' + value.label + '</option>' );
                opt.appendTo( fieldInput );

                if( !counter ) {
                    firstOpt = id;
                }
                counter++;
            }
        });


        if( fieldInput.find( 'option' ).length ) {
            defaultField = defaultField || firstOpt;
            fieldInput.val( defaultField );
            fieldValue.val( defaultValue );

            fieldInput.change( function( ) {
                fieldValue.addClass( 'ui-autocomplete-loading' );
                fieldValue.prop( 'disabled', true );
                fieldValue.find('option').remove( );
                fieldValue.trigger( 'chosen:updated' );
                var col = $( this ).val( );

                //console.log(col);
                _getColumnAutoCompleteData( col )
                    .done( function ( response ) {
                        console.log(response);
                        if( fixValue ) {
                            for( var i in response.assoc ) {
                                for( var j in response.assoc[ i ] ) {
                                    var lbl = response.assoc[ i ][ j ];
                                    var opt = $( '<option>' + lbl + '</option>' );
                                    opt.val( i );
                                    fieldValue.append( opt );
                                }
                            }
                            fieldValue.prop( 'disabled', false );
                            fieldValue.trigger( 'chosen:updated' );
                        } else {
                            fieldValue.autocomplete();
                            fieldValue.autocomplete( "option", "source", response.arr );
                            fieldValue.prop( 'disabled', false );
                        }
                        fieldValue.removeClass( 'ui-autocomplete-loading' );
                    } );
            } );

            _getColumnAutoCompleteData( defaultField )
                .done( function ( response ) {
                    //console.log(response);
                    fieldValue.addClass( 'ui-autocomplete-loading' );
                    fieldValue.prop( 'disabled', true );
                    if( fixValue ) {
                        //console.log("**123**");
                        for( var i in response.assoc ) {
                            for( var j in response.assoc[ i ] ) {
                                var lbl = response.assoc[ i ][ j ];
                                var opt = $( '<option>' + lbl + '</option>' );
                                opt.val( i );
                                if(i === defaultValue)
                                    opt.prop('selected', true);
                                fieldValue.append( opt );

                            }
                        }
                        fieldValue.prop( 'disabled', false );
                        fieldValue.trigger( 'chosen:updated' );
                        fieldValue.val(defaultValue);
                        //console.log('**default Value: **' + fieldValue);
                    } else {
                        fieldValue.autocomplete( {
                            source 	 : response.arr,
                            position : {
                                my: "right top",
                                at: "right bottom",
                                collision: "flipfit flipfit"
                            }
                        } );
                        fieldValue.prop( 'disabled', false );
                    }
                    fieldValue.removeClass( 'ui-autocomplete-loading' );
                } );

            return {
                input   : fieldInput,
                value   : fieldValue
            }
        } else {
            return false;
        }
    };

    var _getColumnAutoCompleteData = function ( column ) {
        if( !( column in _cachedColumns ) ) {
            return $.get(
                TPC_CRM.ajax,
                {
                    action: 'tpc_get_column_autocomplete',
                    column: column
                },
                function( response ) {
                    _cachedColumns[ column ] = response;
                }
            );
        } else {
            return {
                done: function( cb ) {
                    cb( _cachedColumns[ column ] );
                }
            }
        }
    }

    /**
     * ================== Filter Fields ===============================
     * These are functions that'll create the fields in the widget
     * ================================================================
     */

    /**
     * Adds a field for not between two dates
     * @param {string|object} startDate 	Starting Date
     * @param {string|object} endDate   	End Date
     */
    var _addNotBetweenDatesFilter = function( field, startDate, endDate ) {
        /* Making the parameters optional */
        field              = field || "date_registered";
        startDate          = startDate || new Date( ),
            endDate            = endDate || new Date( startDate.getTime( ) + ( 24 * 60 * 60 * 1000 ) );

        /* reformats the string */
        var startDateFormatted = _formatDate( startDate );
        var endDateFormatted   = _formatDate( endDate );

        startDate          = startDateFormatted.obj;
        endDate            = endDateFormatted.obj;

        var from 		 = $( '<input type="text" name="not-between-dates[from]" class="filter-input input-date-from input-datepicker">' ),
            to   		 = $( '<input type="text" name="not-between-dates[to]" class="filter-input input-date-to input-datepicker">' ),
            fromVal      = from.val( ),
            toVal        = to.val ( ),
            fields 		= _createFilterableColumns( field, "date_registered", [ "date" ] );

        if( fields === false ) {
            _showNotification( "No available filters", true );
            return false;
        }

        from.val( startDateFormatted.str );
        from.datepicker()
            .on( 'changeDate', function( ev ) {
                startDate 	= ev.date;

                if( startDate.valueOf( ) > endDate.valueOf( ) ) {
                    from.val( fromVal );
                    _showNotification( 'The start date should be lesser than the end date', true );
                } else {
                    from.datepicker('hide');
                    fromVal = from.val( );
                }
            }).data( 'datepicker' );

        to.val( endDateFormatted.str );
        to.datepicker()
            .on( 'changeDate', function( ev ) {
                endDate 	= ev.date;

                if( startDate.valueOf( ) > endDate.valueOf( ) ) {
                    to.val( toVal );
                    _showNotification( 'The start date should be lesser than the end date', true );
                } else {
                    to.datepicker('hide');
                    toVal = to.val( );
                }
            }).data( 'datepicker' );

        fields.input.attr( "name", "not-between-dates[field]" );
        _createFilterField( 'not_between_dates', function( field ) {
            field
                .append( "If " )
                .append( fields.input )
                .append( ' <br>is not between ' )
                .append( from )
                .append( 'and ' )
                .append( to );

            fields.input.chosen( );
            return field;
        });
    };


    /**
     * Adds a field for between two dates
     * @param {string|object} startDate 	Starting Date
     * @param {string|object} endDate   	End Date
     */
    var _addBetweenDatesFilter = function( field, startDate, endDate ) {
        /* Making the parameters optional */
        field              = field || "date_registered";
        startDate          = startDate || new Date( ),
            endDate            = endDate || new Date( startDate.getTime( ) + ( 24 * 60 * 60 * 1000 ) );

        /* reformats the string */
        var startDateFormatted = _formatDate( startDate );
        var endDateFormatted   = _formatDate( endDate );

        startDate          = startDateFormatted.obj;
        endDate            = endDateFormatted.obj;

        var from 		 = $( '<input type="text" name="between-dates[from]" class="filter-input input-date-from input-datepicker">' ),
            to   		 = $( '<input type="text" name="between-dates[to]" class="filter-input input-date-to input-datepicker">' ),
            fromVal      = from.val( ),
            toVal        = to.val ( ),
            fields 		= _createFilterableColumns( field, "date_registered", [ "date" ] );

        if( fields === false ) {
            _showNotification( "No available filters", true );
            return false;
        }

        from.val( startDateFormatted.str );
        from.datepicker()
            .on( 'changeDate', function( ev ) {
                startDate 	= ev.date;

                if( startDate.valueOf( ) > endDate.valueOf( ) ) {
                    from.val( fromVal );
                    _showNotification( 'The start date should be lesser than the end date', true );
                } else {
                    from.datepicker('hide');
                    fromVal = from.val( );
                }
            }).data( 'datepicker' );

        to.val( endDateFormatted.str );
        to.datepicker()
            .on( 'changeDate', function( ev ) {
                endDate 	= ev.date;

                if( startDate.valueOf( ) > endDate.valueOf( ) ) {
                    to.val( toVal );
                    _showNotification( 'The start date should be lesser than the end date', true );
                } else {
                    to.datepicker('hide');
                    toVal = to.val( );
                }
            }).data( 'datepicker' );

        fields.input.attr( "name", "between-dates[field]" );
        _createFilterField( 'between_dates', function( field ) {
            field
                .append( "If " )
                .append( fields.input )
                .append( ' <br>is between ' )
                .append( from )
                .append( 'and ' )
                .append( to );

            fields.input.chosen( );
            return field;
        });
    };

    /**
     * Adds a Not before date filter field
     * @param {string|object} defaultDate 	Default date
     */
    var _addNotBeforeDate = function( field, defaultDate ) {
        /* make the parameter optional */
        field               = field || "date_registered";
        defaultDate         = defaultDate || new Date( );

        /* reformat date */
        var defaulDateFormatted = _formatDate( defaultDate );
        defaultDate             = defaulDateFormatted.obj;
        var fields              = _createFilterableColumns( field, "date_registered", [ "date" ] );

        if( fields === false ) {
            _showNotification( "No available filters", true );
            return false;
        }

        var dateInput = $( '<input type="text" name="not-before-date[value]" class="filter-input input-date-before input-datepicker">' );
        dateInput.val( defaulDateFormatted.str );
        dateInput.datepicker( );

        fields.input.attr( "name", "not-before-date[field]" );
        _createFilterField( 'not_before_date', function( field ) {
            field
                .append( "If ")
                .append( fields.input )
                .append( ' is not before ')
                .append( dateInput );

            fields.input.chosen( );
            return field;
        } );
    };

    /**
     * Adds a before date filter field
     * @param {string|object} defaultDate 	Default date
     */
    var _addBeforeDate = function( field, defaultDate ) {
        /* make the parameter optional */
        field               = field || "date_registered";
        defaultDate         = defaultDate || new Date( );

        /* reformat date */
        var defaulDateFormatted = _formatDate( defaultDate );
        defaultDate             = defaulDateFormatted.obj;
        var fields              = _createFilterableColumns( field, "date_registered", [ "date" ] );

        if( fields === false ) {
            _showNotification( "No available filters", true );
            return false;
        }

        var dateInput = $( '<input type="text" name="before-date[value]" class="filter-input input-date-before input-datepicker">' );
        dateInput.val( defaulDateFormatted.str );
        dateInput.datepicker( );

        fields.input.attr( "name", "before-date[field]" );
        _createFilterField( 'before_date', function( field ) {
            field
                .append( "If ")
                .append( fields.input )
                .append( ' is before ')
                .append( dateInput );

            fields.input.chosen( );
            return field;
        } );
    };

    /**
     * Adds a Not after date field
     * @param {string|object} defaultDate
     * @param {string|object} field
     */
    var _addNotAfterDate		= function( field, defaultDate ) {
        /* make the parameter optional */
        field               = field || "date_registered";
        defaultDate         = defaultDate || new Date( );

        /* reformat date */
        var defaulDateFormatted = _formatDate( defaultDate );
        defaultDate             = defaulDateFormatted.obj;
        var fields              = _createFilterableColumns( field, "date_registered", [ "date" ] );

        if( fields === false ) {
            _showNotification( "No available filters", true );
            return false;
        }

        var dateInput = $( '<input type="text" name="not-after-date[value]" class="filter-input input-date-after input-datepicker">' );
        dateInput.val( defaulDateFormatted.str );
        dateInput.datepicker( );

        fields.input.attr( "name", "not-after-date[field]" );
        _createFilterField( 'not_after_date', function( field ) {
            field
                .append( "If " )
                .append( fields.input )
                .append( ' is not after ')
                .append( dateInput );

            fields.input.chosen( );
            return field;
        } );
    };

    /**
     * Adds an after date field
     * @param {string|object} defaultDate 	Default date
     */
    var _addAfterDate		= function( field, defaultDate ) {
        /* make the parameter optional */
        field               = field || "date_registered";
        defaultDate         = defaultDate || new Date( );

        /* reformat date */
        var defaulDateFormatted = _formatDate( defaultDate );
        defaultDate             = defaulDateFormatted.obj;
        var fields              = _createFilterableColumns( field, "date_registered", [ "date" ] );

        if( fields === false ) {
            _showNotification( "No available filters", true );
            return false;
        }

        var dateInput = $( '<input type="text" name="after-date[value]" class="filter-input input-date-after input-datepicker">' );
        dateInput.val( defaulDateFormatted.str );
        dateInput.datepicker( );

        fields.input.attr( "name", "after-date[field]" );
        _createFilterField( 'after_date', function( field ) {
            field
                .append( "If " )
                .append( fields.input )
                .append( ' is after ')
                .append( dateInput );

            fields.input.chosen( );
            return field;
        } );
    };


    /**
     * Adds a Not lesser than field
     * @param {string} defaultField 	Default Field Name
     * @param {string} defaultValue 	Default Fiel Value
     */
    var _addNotLesserThanField	= function ( defaultField, defaultValue ) {
        var fields = _createFilterableColumns( defaultField, defaultValue, [ "number", "date" ] );

        if( fields === false ) {
            _showNotification( "No available filters", true );
            return false;
        }

        fields.input.attr( "name", "not-lesser-than[field][]" );
        fields.value.attr( "name", "not-lesser-than[value][]" );
        _createFilterField( 'field_not_lesser_than', function( field ) {
            field
                .append( 'If ' )
                .append( fields.input )
                .append( 'is not lesser than ' )
                .append( fields.value );

            fields.input.chosen( );
            return field;
        } );
    };

    /**
     * Adds a lesser than field
     * @param {string} defaultField 	Default Field Name
     * @param {string} defaultValue 	Default Fiel Value
     */
    var _addLesserThanField	= function ( defaultField, defaultValue ) {
        var fields = _createFilterableColumns( defaultField, defaultValue, [ "number", "date" ] );

        if( fields === false ) {
            _showNotification( "No available filters", true );
            return false;
        }

        fields.input.attr( "name", "lesser-than[field][]" );
        fields.value.attr( "name", "lesser-than[value][]" );
        _createFilterField( 'field_lesser_than', function( field ) {
            field
                .append( 'If ' )
                .append( fields.input )
                .append( 'is lesser than ' )
                .append( fields.value );

            fields.input.chosen( );
            return field;
        } );
    };

    /**
     * Adds a Not greater than field
     * @param {string} defaultField 	Default Field Name
     * @param {string} defaultValue 	Default Field Value
     */
    var _addNotGreaterThanField = function ( defaultField, defaultValue ) {
        var fields = _createFilterableColumns( defaultField, defaultValue, [ "number", "date" ] );

        if( fields === false ) {
            _showNotification( "No available filters", true );
            return false;
        }

        fields.input.attr( "name", "not-greater-than[field][]" );
        fields.value.attr( "name", "not-greater-than[value][]" );
        _createFilterField( 'field_not_greater_than', function( field ) {
            field
                .append( 'If ' )
                .append( fields.input )
                .append( 'is not greater than ' )
                .append( fields.value );

            fields.input.chosen( );
            return field;
        } );
    };

    /**
     * Adds a greater than field
     * @param {string} defaultField 	Default Field Name
     * @param {string} defaultValue 	Default Field Value
     */
    var _addGreaterThanField = function ( defaultField, defaultValue ) {
        var fields = _createFilterableColumns( defaultField, defaultValue, [ "number", "date" ] );

        if( fields === false ) {
            _showNotification( "No available filters", true );
            return false;
        }

        fields.input.attr( "name", "greater-than[field][]" );
        fields.value.attr( "name", "greater-than[value][]" );
        _createFilterField( 'field_greater_than', function( field ) {
            field
                .append( 'If ' )
                .append( fields.input )
                .append( 'is greater than ' )
                .append( fields.value );

            fields.input.chosen( );
            return field;
        } );
    };

    /**
     * Adds an not equals field
     * @param {string} defaultField 	Default Field Name
     * @param {string} defaultValue 	Default Field Value
     */
    var _addNotEqualsField 	= function ( defaultField, defaultValue ) {
        var fields = _createFilterableColumns( defaultField, defaultValue, [ "number", "date", "string" ], true );
        //console.log(fields);
        if( fields === false ) {
            _showNotification( "No available filters", true );
            return false;
        }

        fields.input.attr( "name", "not-equals[field][]" );
        fields.value.attr( "name", "not-equals[value][]" );
        _createFilterField( 'field_not_equal', function( field ) {
            field
                .append( 'If ' )
                .append( fields.input )
                .append( 'not equals to ' )
                .append( fields.value );

            fields.input.chosen( );
            fields.value.chosen( );
            // fields.input.change( function( ) {
            // 	fields.value.trigger( 'chosen:updated' );
            // } );
            return field;
        } );
    };


    /**
     * Adds an equals field
     * @param {string} defaultField 	Default Field Name
     * @param {string} defaultValue 	Default Field Value
     */
    var _addEqualsField 	= function ( defaultField, defaultValue ) {
        //console.log( defaultField + '*****' + defaultValue)
        var fields = _createFilterableColumns( defaultField, defaultValue, [ "number", "date", "string" ], true );
        //console.log(fields);
        //return false;
        if( fields === false ) {
            _showNotification( "No available filters", true );
            return false;
        }

        fields.input.attr( "name", "equals[field][]" );
        fields.value.attr( "name", "equals[value][]" );

        _createFilterField( 'field_equal', function( field ) {
            field
                .append( 'If ' )
                .append( fields.input )
                .append( 'is equals to ' )
                .append( fields.value );

            fields.input.chosen( );
            fields.value.chosen( );
            // fields.input.change( function( ) {
            // 	fields.value.trigger( 'chosen:updated' );
            // } );
            return field;
        } );
    };


    /**
     * Adds a does not contain field
     * @param {string} defaultField 	Default Field Name
     * @param {string} defaultValue 	Default Field Value
     */
    var _addNotContainsField = function ( defaultField, defaultValue ) {
        var fields = _createFilterableColumns( defaultField, defaultValue, [ "string" ] );

        if( fields === false ) {
            _showNotification( "No available filters", true );
            return false;
        }

        fields.input.attr( "name", "not-contains[field][]" );
        fields.value.attr( "name", "not-contains[value][]" );
        _createFilterField( 'field_not_contains', function( field ) {
            field
                .append( 'If ' )
                .append( fields.input )
                .append( 'does not contain ' )
                .append( fields.value );

            fields.input.chosen( );
            return field;
        } );
    }

    /**
     * Adds a contains field
     * @param {string} defaultField 	Default Field Name
     * @param {string} defaultValue 	Default Field Value
     */
    var _addContainsField = function ( defaultField, defaultValue ) {
        var fields = _createFilterableColumns( defaultField, defaultValue, [ "string" ] );

        if( fields === false ) {
            _showNotification( "No available filters", true );
            return false;
        }

        fields.input.attr( "name", "contains[field][]" );
        fields.value.attr( "name", "contains[value][]" );
        _createFilterField( 'field_contains', function( field ) {
            field
                .append( 'If ' )
                .append( fields.input )
                .append( 'contains ' )
                .append( fields.value );

            fields.input.chosen( );
            return field;
        } );
    }

    /**
     * Add the default filters
     */
    var _resetFilters = function( u ) {
        u.searchFilterDropdown.find( '.default-filters' ).remove( );
        filtered = [];

        u.searchFilterDropdown.append( '<option value="0" id="emptyUserFilters" class="default-filters"></option>' );
        var i;
        for( i in u.options.filters ) {
            var id  	= i,
                label 	= u.options.filters[ i ];
            filters[ id ] = $( '<option value="' + id + '" class="default-filters">' + label + '</options>' );
            u.searchFilterDropdown.append( filters[ id ] );
        }
    }
    /**
     * Hiding and Showing Columns and columns Data
     */
    var _hideColumns = function() {
        $($hideToggleClass).each(function(){

            var thisValS = $(this).val();
            var isChecked = ($(this).attr('checked') == 'checked'); //.triggerHandler('click', {crm_fired: 'true'});
            var table = $('.wp-list-table');
            var th = table.find('thead th, thead td');
            var tf = table.find('tfoot th, tfoot td');
            var counter = 0;
            //var posFound = 0;
            th.each(function() {
                counter++;
                if($(this).attr('id') == thisValS) {

                    if(isChecked === false) {
                        console.log(1);
                        $(this).addClass('hidden');
                        $('.column-'+thisValS).addClass('hidden');
                        console.log($('.column-'+thisValS).attr('class'));
                        $(this).hide();
                    } else {
                        console.log(2);
                        $(this).removeClass('hidden');
                        $('.column-'+thisValS).removeClass('hidden');
                        $(this).show();
                    }
                    return false;
                }

            });


            var td = table.find('tbody tr td:nth-child(' + (counter) + ')');
            td.each(function() {

                if(isChecked === false) {
                    $(this).addClass('hidden');
                    $(this).hide();
                } else {
                    $(this).removeClass('hidden');
                    $(this).show();
                }
            });
        });
    }
    /**
     * Create the Filters sidebar widget
     * @param  {object} $this Pointer to the parent function
     */
    var _displayFilters = function( $this ) {
        $this.searchForm        = $( '#DataTables_Table_0_filter' );
        $this.searchInput       = $this.searchForm.find( 'label:has(.search-input)' );
        $this.searchField       = $this.searchInput.find( '[type="search"]' );
        $this.toggleFilters     = $( '#toggle-filters' );
        $this.toggleFiltersIcon = $( '#toggle-filters-icon' );
        $this.notificationDiv   = $( '<div class="notification-div updated message hidden"></div>');

        /* Add the notification div */
        $( '.wrap' ).prepend( $this.notificationDiv );

        /* Add the special columns */
        var username = $( '<input class="hide-column-tog" name="username-hide" type="checkbox" id="username-hide" value="username">' );
        var name     = $( '<input class="hide-column-tog" name="name-hide" type="checkbox" id="name-hide" value="name">' );
        $( '#screen-options-wrap #adv-settings .metabox-prefs' )
            .prepend( username )
            .prepend( name );
        username.after( '<label for="username-hide">Username</label>' );
        name.after( '<label for="name-hide">Name</label>' );
        if( $.inArray( 'username', TPC_CRM.hidden ) < 0 ) {
            username.prop( 'checked', true );
        }
        if( $.inArray( 'name', TPC_CRM.hidden ) < 0 ) {
            name.prop( 'checked', true );
        }

        /* add style the search form */
        $this.searchForm
            .addClass( 'filters-box post-box' )
            .prepend( '<div class="metabox-heading"><h3>Search and Filters</h3></div><ul id="search-filters-tabs" class="nav nav-tabs clearfix"><li id="nav-tab-search-tab"><a href="#search-tab">Search &amp; Filters</a></li><li id="nav-tab-presets-tab"><a href="#presets-tab">Presets</a></li></ul>' );
        $( '.wp-list-table' ).addClass( 'has-filters' );
        $( '#search-submit' ).addClass( 'hidden' );

        $this.searchInput
            .wrap( '<div id="search-filters-tabs"><div id="search-tab" class="tab clearfix">' );
        $this.searchField.attr( 'placeholder', 'Global Search' )

        $( '#DataTables_Table_0_first' ).html( '&laquo;' );
        $( '#DataTables_Table_0_previous' ).html( '&lsaquo;' );
        $( '#DataTables_Table_0_next' ).html( '&rsaquo;' );
        $( '#DataTables_Table_0_last' ).html( '&raquo;' );
        $( '.wp-list-table th a' ).on( 'click', function( e ) {
            e.preventDefault( );
        } );

        $this.searchFiltersTabs = $( '#search-filters-tabs' );
        $this.toggleFilters.click( function( e ) {
            e.preventDefault( );
            if( $this.searchForm.hasClass( 'active' ) ) {
                $this.searchForm.removeClass( 'active');
                $this.toggleFiltersIcon
                    .removeClass( 'glyphicon-chevron-up' )
                    .addClass( 'glyphicon-chevron-down' );
            } else {
                $this.searchForm.addClass( 'active');
                $this.toggleFiltersIcon
                    .removeClass( 'glyphicon-chevron-down' )
                    .addClass( 'glyphicon-chevron-up' );
            }
        } );

        $( document ).click( function ( event ) {
            if( !$( event.target ).closest( '#DataTables_Table_0_filter' ).length ) {
                $this.searchForm.removeClass( 'active');
                $this.toggleFiltersIcon
                    .removeClass( 'glyphicon-chevron-up' )
                    .addClass( 'glyphicon-chevron-down' );
            }
        } );

        $( '.datepicker td' ).click( function() {
            $this.searchForm.addClass( 'active');
            $this.toggleFiltersIcon
                .removeClass( 'glyphicon-chevron-down' )
                .addClass( 'glyphicon-chevron-up' );
        } );

        $this.searchForm.hover( function( ) {
            $( this ).addClass( 'active');
            $this.toggleFiltersIcon
                .removeClass( 'glyphicon-chevron-down' )
                .addClass( 'glyphicon-chevron-up' );
        }, function( ) {
            // $( this ).removeClass( 'active' );
            // $this.toggleFiltersIcon
            // 	.removeClass( 'glyphicon-chevron-up' )
            // 	.addClass( 'glyphicon-chevron-down' );
        } );

        /* wrap it inside the search tab */
        $this.searchTab 	   = $( '#search-tab' );

        /* Add elements in the search tab */
        $this.searchFilterDropdown = $( '<select tabindex="2" data-placeholder="Select a filter.." id="userFilters" class="select-chosen"></select>' );
        $this.searchFilterDropdown.appendTo( $this.searchTab );
        $this.searchFilterDropdown.wrap( '<div class="filter-field-dropdown"></div>' );
        $this.filters.reset( $this );

        /* Add the footer */
        $this.searchTab.append( '<div id="filters-fields"></div><div class="metabox-footer"><div id="metabox-footer-extra"><em>Upgrade to premium so you can save presets.</em></div></div>');
        $this.searchTabFilters     = $( '#filters-fields' );
        $this.searchTabFooter      = $( $this.searchTab.find( '#metabox-footer-extra' ) );
        $this.resetFilter          = $( '.reset-users-button' );

        /**
         * Resets the form
         * @param  {Object} e 		Event Object
         */
        $this.resetFilter.click( function( e ) {
            dataLoaded = false;
            e.preventDefault( );
            $this.searchField.val( '' );
            _removeFilters( );
            $this.userDataTable.fnResetAllFilters( );
            fnApplyFilters( );
            $this.userDataTable.fnReloadAjax( );
            //console.log(resetFilterHooks.length + ' >>>>');

            for (var i=0 ; i<resetFilterHooks.length ; i++) {
                //console.log(resetFilterHooks[i]);
                resetFilterHooks[i]();
            }

            $('.custom-hide-column-tog').each(function(e) {
                if ($(this).is(':checked')) $(this).prop('checked', false);
            });
        } );

        /* Add filters from the request */
        if( $this.options.requests.length ) {
            _addFilters( $this.options.requests );
        }

        /* Actions to the dropdown */
        $this.searchFilterDropdown
            .chosen( )
            .change( function( e ) {
                var val = $( this ).val( );
                $this.userDataTable.fnReloadAjax( );

                $( this ).val( 0 );
                $( this )
                    .trigger( 'chosen:updated' )
                    .trigger( 'userFiltersChanged', [ val ] );
                //alert(val);
                val = _checkFilter( val );

                /* check if $this is a preset */
                switch( val ) {
                    case "between_dates":
                        _addBetweenDatesFilter( );
                        $('.filter-inputs input[name="between-dates[from]"]').trigger('change');
                        break;
                    case "not_between_dates":
                        _addNotBetweenDatesFilter( );
                        $('.filter-inputs input[name="not-between-dates[from]"]').trigger('change');
                        break;
                    case "before_date":
                        _addBeforeDate( );
                        $('.filter-inputs input[name="before-date[value]"]').trigger('change');
                        break;
                    case "not_before_date":
                        _addNotBeforeDate( );
                        $('.filter-inputs input[name="not-before-date[value]"]').trigger('change');
                        break;
                    case "after_date":
                        _addAfterDate( );
                        $('.filter-inputs input[name="after-date[value]"]').trigger('change');
                        break;
                    case "not_after_date":
                        _addNotAfterDate( );
                        $('.filter-inputs input[name="not-after-date[value]"]').trigger('change');
                        break;
                    case "field_lesser_than":
                        _addLesserThanField( );
                        break;
                    case "field_not_lesser_than":
                        _addNotLesserThanField( );
                        break;
                    case "field_greater_than":
                        _addGreaterThanField( );
                        break;
                    case "field_not_greater_than":
                        _addNotGreaterThanField( );
                        break;
                    case "field_equal":
                        _addEqualsField( );
                        break;
                    case "field_not_equal":
                        _addNotEqualsField( );
                        break;
                    case "field_contains":
                        _addContainsField( );
                        break;
                    case "field_not_contains":
                        _addNotContainsField();
                    default: break;
                }

                _fireEvent( 'add.filter', val );
            } );

        /* Add the presets tab */
        $this.searchTab.after( '<div id="presets-tab" class="tab clearfix"><div class="inner"></div></div>' );
        $this.presetsTab = $( '#presets-tab .inner' );
        $this.presetsTab.html( 'Presets' );

        $( '#nav-tab-search-tab a' ).tab( 'show' );
        $( '#search-filters-tabs a' ).click( function( e ) {
            e.preventDefault();

            $( this ).tab( 'show' );
        } );
    }

    /**
     * ============ Public variables and functions =======================
     * These are variables and function available for public use.
     * ===================================================================
     */
    return {
        /**
         * Initialize the object and build the widget
         * @param  {Object} options 	Settings to override the default settings
         */
        init : function( options ) {
            if( instance ) return this;

            var $this   = this;
            that        = this;
            this.options = $.extend( { }, defaults, options );

            _extendDataTable( this );
            _addDataTable( this );
            _displayFilters( this );


// 			$( $hideToggleClass ).on('click', function(evt, crm_fired) {
// 				//evt.preventDefault();
// 				console.log( crm_fired );
// 				if ( typeof crm_fired != 'undefined' ) {
// 					console.log( 'this is a test' );
// 					return false;
// 					//$( this ).trigger('screenOptionUpdate');
// 				} else {
// 					console.log( 'A normal event' );
// 					$( this ).trigger('screenOptionUpdate');
// 				}


// 			});
            function debounce(fn, delay) {
                var timer = null;
                return function () {
                    var context = this, args = arguments;
                    clearTimeout(timer);
                    timer = setTimeout(function () {
                        fn.apply(context, args);
                    }, delay);
                };
            }
            function throttle(fn, threshhold, scope) {
                threshhold || (threshhold = 250);
                var last,
                    deferTimer,
                    kounter = 0;
                //return function () {
                var context = scope || this;

                var now = +new Date,
                    args = arguments;

                if (kounter >= 20) {
                    //fn.apply(context, args);
                    kounter = 0;
                }
                if (last && now < last + threshhold) {
                    // hold on to it
                    //clearTimeout(deferTimer);
// 						deferTimer = setTimeout(function () {
// 							last = now;
// 							//fn.apply(context, args);
// 						}, threshhold);

                } else {
                    last = now;
                    //fn.apply(context, args);
                }
                //};
            }
            var ajaxKounter = 0, xhr;
// 			$( $hideToggleClass ).off( 'click' );
// 			$( $hideToggleClass ).unbind( 'click.hide-column-tog' );
            $('.hide-column-tog').removeClass('hide-column-tog').addClass('hide-column-tog-crm');
            var getColumnsForHiding = function() {
                var hiddenColumnsVals = new Array();
                $($hideToggleClass).each(function() {
                    if ($(this).attr('checked') != 'checked') hiddenColumnsVals.push($(this).val());
                })
                //console.log(hiddenColumnsVals);
                return hiddenColumnsVals.join(',');
            }
            getColumnsForHiding();
            $(  $hideToggleClass ).on( 'click',  debounce( function(evt, crm_fired ) {
                console.log( crm_fired );
                //if ( ajaxKounter >= 20 && (typeof xyz == 'undefined') ) {
                console.log( ajaxKounter );
                $this.showLoadingScreen( );
                xhr = $.post(
                    TPC_CRM.ajax,
                    {
                        action 				: "hidden-columns",
                        callId				: 'crm-free-hidden-columns',
                        hidden 				:  getColumnsForHiding(), // columns.hidden,
                        screenoptionnonce 	: $("#screenoptionnonce").val(),
                        page 				: pagenow
                    },
                    function (data ) {
                        $this.userDataTable.fnReloadAjax( );
                    }
                ).done(function() {
                    console.log( 'click event is owkring' );
                    _hideColumns();
                });
                ajaxKounter = 0;
                //} else {
                ajaxKounter++;
                //}

// 				if ( ajaxKounter < 20 ) {
// 					xhr.abort();
// 					ajaxKounter++;
// 					console.log( 'Aborted' );
// 				} else {
// 					ajaxKounter = 0;
// 				}
            }, 500 ) );

            $( '.search-input input[type="search"]' ).on( 'keyup', function(e){
                var checker = setInterval(function(){
                    fnApplyFilters( );
                    $this.userDataTable.fnReloadAjax( );
                    clearInterval(checker);
                }, 1000);

            });

            instance = true;
            return this;
        },

        /**
         * Display the loading icon
         */
        showLoadingScreen : _showLoadingScreen,

        /**
         * Hide the loading screen
         */
        hideLoadingScreen : _hideLoadingScreen,
        /**
         * Hide Show User table Columns and Columns Data
         */
        hideColumns : _hideColumns,
        /**
         * Get the table form
         */
        getForm : _getForm,

        /**
         * Gets the serialize form
         */
        getSerializedForm : _getSerializedForm,

        /**
         * Add a way to access the filters helpers
         * @type {Object}
         */
        filters : {
            draw 					: _addFilters,
            hideCol                : _hideColumns,
            flush 					: _removeFilters,
            add 					: _addCustomFilter,
            reset 					: _resetFilters,
            addBetweenDatesFilter 	: _addBetweenDatesFilter,
            addBeforeDate 			: _addBeforeDate,
            addAfterDate 			: _addAfterDate,
            addLesserThanField 		: _addLesserThanField,
            addGreaterThanField 	: _addGreaterThanField,
            addEqualsField 			: _addEqualsField,
            addContainsField 		: _addContainsField,
            checkFilter 			: _checkFilter,
            isRemovable 			: _isRemovable,
            isRemoved 				: _isRemoved
        },

        /**
         * Show the notification
         * @type {Function}
         */
        notify : _showNotification,

        /**
         * Gives a way to hook to an event
         * @type {function}
         */
        on : _addEvent
    }
}( jQuery );
