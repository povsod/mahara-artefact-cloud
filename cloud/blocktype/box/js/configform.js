// Copied from 'checkboxes' Pieform element to allow correct functionality
// of All and None links (for selecting all or none of the checkboxes)
function pieform_element_checkboxes_update(p, v) {
    forEach(getElementsByTagAndClassName('input', 'checkboxes', p), function(e) {
        if (!e.disabled) {
            e.checked = v;
        }
    });
}