jQuery(document).ready(function($) {
    
    // Accordion "ebh-logs" (debug): 
    if ($('#ebh-logs').length != 0) {    
        $('#ebh-logs').accordion({
            header: 'h4',
            collapsible: true,
            active: false,
            icons: { 
                'header': 'ui-icon-plus', 
                'activeHeader': 'ui-icon-minus' 
            }
        }).find('.ebh-log-directory-download, .ebh-log-directory-delete').on('click', function(e) {
            e.stopPropagation();
        });    
    }

    // Accordion "ebh-hooks" (help):
    if ($('#ebh-hooks').length != 0) {
        $('#ebh-hooks').accordion({
            header: 'h4',
            collapsible: true,
            active: false,
            icons: { 
                'header': 'ui-icon-plus', 
                'activeHeader': 'ui-icon-minus' 
            }
        });
    }
});