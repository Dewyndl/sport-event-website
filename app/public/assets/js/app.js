document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('input[type="time"]').forEach(function(el) {
        var current = el.value;
        flatpickr(el, {
            enableTime: true,
            noCalendar: true,
            dateFormat: 'H:i',
            time_24hr: true,
            defaultDate: current || null,
        });
    });

    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (!confirm(el.dataset.confirm)) {
                e.preventDefault();
            }
        });
    });

    var prevSelect = document.getElementById('prev-participant');
    var prevSearch = document.getElementById('prev-participant-search');
    var prevDropdown = document.getElementById('prev-participant-dropdown');
    if (prevSelect && prevSearch && prevDropdown) {
        var allOptions = Array.from(prevSelect.options)
            .filter(function(o) { return o.value; })
            .map(function(o) {
                return { value: o.value, fio: o.dataset.fio || '', phone: o.dataset.phone || '', email: o.dataset.email || '' };
            });

        function fillFields(o) {
            document.getElementById('add-member-fio').value = o.fio;
            document.getElementById('add-member-phone').value = o.phone;
            document.getElementById('add-member-email').value = o.email;
            document.getElementById('add-member-fio').readOnly = true;
            prevSearch.value = o.fio;
            prevDropdown.style.display = 'none';
        }

        function clearFields() {
            document.getElementById('add-member-fio').value = '';
            document.getElementById('add-member-phone').value = '';
            document.getElementById('add-member-email').value = '';
            document.getElementById('add-member-fio').readOnly = false;
        }

        function renderDropdown(q) {
            var filtered = q
                ? allOptions.filter(function(o) { return o.fio.toLowerCase().includes(q.toLowerCase()); })
                : allOptions;
            prevDropdown.innerHTML = '';
            if (!filtered.length) {
                prevDropdown.style.display = 'none';
                return;
            }
            filtered.forEach(function(o) {
                var item = document.createElement('button');
                item.type = 'button';
                item.className = 'list-group-item list-group-item-action';
                item.textContent = o.fio + (o.phone ? ' — ' + o.phone : '');
                item.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                    fillFields(o);
                });
                prevDropdown.appendChild(item);
            });
            prevDropdown.style.display = 'block';
        }

        prevSearch.addEventListener('input', function() {
            clearFields();
            renderDropdown(this.value);
        });

        prevSearch.addEventListener('focus', function() {
            renderDropdown(this.value);
        });

        prevSearch.addEventListener('blur', function() {
            setTimeout(function() { prevDropdown.style.display = 'none'; }, 150);
        });

        document.addEventListener('click', function(e) {
            if (!prevSearch.contains(e.target) && !prevDropdown.contains(e.target)) {
                prevDropdown.style.display = 'none';
            }
        });
    }
});
