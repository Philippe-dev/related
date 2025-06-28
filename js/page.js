'use strict';
dotclear.ready(() => {
    const up_file = document.getElementById('up_file');
    const repository_file = document.getElementById('repository_file');

    if (up_file.value) {
        repository_file.required = false;
    }
    if (repository_file.value) {
        up_file.required = false;
    }

    repository_file.addEventListener('change', function () {
        if (this.value) {
            up_file.required = false;
        }
    });
    up_file.addEventListener('change', function () {
        if (this.value) {
            repository_file.required = false;
        }
    });
})