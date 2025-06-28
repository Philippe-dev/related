'use strict';
dotclear.ready(() => {
    document.getElementById('repository_file').addEventListener('change', function () {
        const up_file = document.getElementById('up_file');
        if (this.value) {
            up_file.required = false;
        }
    });
    document.getElementById('up_file').addEventListener('change', function () {
        const repository_file = document.getElementById('repository_file');
        if (this.value) {
            repository_file.required = false;
        }
    });
})