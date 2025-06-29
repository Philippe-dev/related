'use strict';

dotclear.ready(() => {
    const up_file = document.getElementById('up_file');
    const files_dir = document.getElementById('files_dir');

    function updateRequiredFields() {
        up_file.required = !files_dir.value;
        files_dir.required = !up_file.value;
    }

    updateRequiredFields();

    files_dir.addEventListener('change', updateRequiredFields);
    up_file.addEventListener('change', updateRequiredFields);
});
