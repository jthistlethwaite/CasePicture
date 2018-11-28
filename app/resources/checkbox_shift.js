function shiftCheckboxes()
{
    var $chkboxes = $('.chkbox:visible');

    console.log($chkboxes);

    var lastChecked = null;

    $chkboxes.click(function(e) {
        if(!lastChecked) {
            lastChecked = this;
            return;
        }

        if(e.shiftKey) {
            var start = $chkboxes.index(this);
            var end = $chkboxes.index(lastChecked);

            console.log($chkboxes.slice(Math.min(start,end), Math.max(start,end)+ 1));

            $chkboxes.slice(Math.min(start,end), Math.max(start,end)+ 1).each(function (e) {
                /*
                 * Changed 2018-11-16
                 *
                 * This is a bug fix so multi-select only acts on visible check boxes.
                 *
                 * This is needed because of the way the searchable tables work on the rest of the site. This previously
                 * toggled checkboxes of hidden rows, but this change should fix that.
                 */
                if ( $(this).is(":visible") ) {
                    $(this).prop('checked', lastChecked.checked);
                }
            });

            // $chkboxes.slice(Math.min(start,end), Math.max(start,end)+ 1).prop('checked', lastChecked.checked);

        }

        lastChecked = this;
    });
}