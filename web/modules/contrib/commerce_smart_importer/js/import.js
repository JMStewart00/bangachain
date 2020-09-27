/**
 * @file
 */

jQuery(document).ready(function () {
    jQuery('.override-button').on('click', function () {

        var override_div = jQuery(this).prev();
        var value = override_div.children('.override-value').val();
        var row = jQuery(this).attr('data-row');
        var field_index = jQuery(this).attr('data-field');
        var field_type = jQuery(this).attr('data-type');
        var import_name = findParameter('import_name');
        var status = override_div.children('.override-status');

        jQuery.ajax(
            {
                url: '/admin/commerce-smart-override',
                data: {
                    import_name: import_name,
                    value: value,
                    row: row,
                    index: field_index,
                    field_type: field_type,
                },
                method: 'POST',
                success: function (data) {
                    if (data.includes("unsuccessful")) {
                        changeStatus(status, 'incorrect');
                    }
                    else {
                        changeStatus(status, 'correct');
                    }
                }
            }
        );
    });

    function findParameter(parameter) {
        var vars = window.location.search.split('&');
        var temp_var_name;
        for (var key in vars) {
            temp_var_name = vars[key].split('=');
            if (temp_var_name[0] == parameter) {
                return temp_var_name[1];
            }
        }
        return '';
    }

    function changeStatus(element, status) {
        element.removeClass();
        element.addClass('override-status ' + status)
    }
});
