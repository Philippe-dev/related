'use strict';

dotclear.ready(() => {
    const up_file = document.getElementById('up_file');
    const repository_file = document.getElementById('files_dir');

    function updateRequiredFields() {
        up_file.required = !repository_file.value;
        repository_file.required = !up_file.value;
    }

    updateRequiredFields();

    repository_file.addEventListener('change', updateRequiredFields);
    up_file.addEventListener('change', updateRequiredFields);
});
