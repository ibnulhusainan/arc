/**
 * ===================================
 * ARC.JS - Modular CRUD Helper
 * ===================================
 */
const Arc = {
    pk : '',

    init (args) {
        Arc.pk = args.pk || '';

        return this;
    },
    // ========================
    // Core Utilities
    // ========================
    util: {
        csrf() {
            return document.querySelector('meta[name="_token"]')?.content || '';
        },
        route(key, fallback = null) {
            return window.routes?.[key] ?? fallback;
        },
        json(res) {
           return res.ok ? res.json() : Promise.reject('Failed');
        }        
    },

    // ========================
    // Validation
    // ========================
    validate: {
        form(form, rules = {}) {
            form.querySelectorAll('small').forEach(s => s.textContent = '');
            const errors = [];

            for (const [field, ruleStr] of Object.entries(rules)) {
                const input = form.querySelector(`[name="${field}"]`);
                if (!input) continue;

                const fieldErrors = Arc.validate.field(input, ruleStr, form);
                fieldErrors.forEach(msg => errors.push({ field, msg }));
            }
            return errors;
        },

        field(input, ruleStr, form = null) {
            const label = input.previousElementSibling?.textContent || input.name;
            const rules = ruleStr.split('|');
            const value = input.value.trim();
            const errors = [];

            for (const r of rules) {
                const [rule, param] = r.split(':');
                const fn = Arc.validate.rules[rule];
                if (typeof fn === 'function' && !fn(value, param, form)) {
                    errors.push(Arc.message(rule, param, label));
                }
            }
            return errors;
        },

        message(rule, param, label) {
            const msgs = {
                required: `${label} is required`,
                email: `${label} must be a valid email`,
                url: `${label} must be a valid URL`,
                maxlength: `${label} must be at most ${param} characters`,
                minlength: `${label} must be at least ${param} characters`,
                number: `${label} must be a number`,
                integer: `${label} must be an integer`,
                pattern: `${label} format is invalid`,
                match: `${label} must match ${param}`,
            };
            return msgs[rule] ?? `${label} is invalid`;
        },

        rules: {
            required: v => v.length > 0,
            email: v => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v),
            url: v => { try { new URL(v); return true; } catch { return false; } },
            maxlength: (v, n) => v.length <= +n,
            minlength: (v, n) => v.length >= +n,
            number: v => !isNaN(v),
            integer: v => /^-?\d+$/.test(v),
            pattern: (v, re) => new RegExp(re).test(v),
            match: (v, field, form) => !form || v === form.querySelector(`[name="${field}"]`)?.value,
        },
    },

    // ========================
    // Form Actions
    // ========================
    form: {
        save(formId, options = {}) {
            const form = document.getElementById(formId);
            if (!form) return;

            form.addEventListener('submit', e => {
                e.preventDefault();

                const errors = Arc.validate.form(form, options.rules || {});
                if (errors.length) return Arc.notif(errors, 'error', form);

                const formData = new FormData(form);
                Arc.fetch.save(formData, form);
            });
        },

        cancel() {
            if (Arc.util.route('list')) location.replace(Arc.util.route('list'));
        },
    },

    // ========================
    // List & DataTable
    // ========================
    list: {
        delete(id) {
            const pk = Arc.pk;
            if (!confirm('Are you sure you want to delete this item?')) return;
            Arc.fetch.delete({[pk]: id});
        },

        datatableLayout() {
            const pk = Arc.pk;

            return {
                top: [
                    { buttons: ['pageLength'] },
                    { buttons: [
                        { text: '<i class="fa fa-plus"></i>', action: () => location.href = Arc.util.route('form') },
                        { text: '<i class="fa fa-edit"></i>', action: (e, dt) => {
                            const data = dt.rows({ selected: true }).data();
                            if (data.length !== 1)
                                return Arc.swalert.warning({
                                    title: data.length ? "Too Many Selected" : "No Data Selected",
                                    text: "Please select exactly one data to edit",
                                });
                            location.href = `${Arc.util.route('form')}/${data[0][pk]}`;
                        }},
                        { text: '<i class="fa fa-trash"></i>', action: (e, dt) => {
                            const data = Object.values(dt.rows({ selected: true }).data()).filter(v => v && v[pk]).map(v => v[pk]);
                            if (!data.length)
                                return Arc.swalert.warning({ title: "No Data Selected", icon: "warning" });

                            Arc.swalert.confirm({
                                title: "Are you sure?",
                                text: `Delete ${data.length} item(s)? This cannot be undone.`,
                            }).then(result => {
                                if (!result.isConfirmed) return;
                                Arc.fetch.delete({[pk]: data});
                                dt.ajax.reload();
                            });
                        }}
                    ]},
                    'search'
                ],
                topStart: null,
                topEnd: null
            };
        }
    },

    // ========================
    // Fetch
    // ========================
    fetch: {
        save: (data, form) => {
            fetch(Arc.util.route('save', form.action), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': Arc.util.csrf(),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: data,
            })
            .then(Arc.util.json)
            .then(data => {
                if (data.success) {
                    Arc.swalert.success({title: 'Success', text: 'Data saved successfully!'});
                    setTimeout(() => location.href = Arc.util.route('list', location.href), 500);
                } else {
                    const errs = Object.entries(data.errors || {}).map(([f, m]) => ({ field: f, msg: m.join(', ') }));
                    Arc.notif(errs, 'error', form);
                }
            })
            .catch(() => Arc.swalert.error({title: 'Error', text: 'Failed to save data!'}));
        },

        delete: (data) => {
            fetch(Arc.util.route('delete'), {
                method: "DELETE",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    "Accept": "application/json",
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": Arc.util.csrf()
                },
                body: JSON.stringify(data)
            })
            .then(Arc.util.json)
            .then(res => {
                if (res.success) {
                    Arc.swalert.success({ title: "Deleted!", text: res.message ?? "Data deleted successfully." });
                } else Arc.swalert.error({ title: "Error", text: "Failed to delete data." });
            })
            .catch(() => Arc.swalert.error({ title: "Error", text: "Something went wrong." }));
        }
    },


    // ========================
    // Sweet Alert
    // ========================
    swalert: {
        success: ({title = "Success", text = "", timer = 1500}) => {
            return Swal.fire({
                icon: "success",
                title,
                text,
                timer,
                showConfirmButton: false
            });
        },
    
        error: ({title = "Error", text = "", footer = null}) => {
            return Swal.fire({
                icon: "error",
                title,
                text,
                footer
            });
        },
    
        warning: ({title = "Warning", text = "", confirmText = "OK"}) => {
            return Swal.fire({
                icon: "warning",
                title,
                text,
                confirmButtonText: confirmText
            });
        },
    
        info: ({title = "Info", text = ""}) => {
            return Swal.fire({
                icon: "info",
                title,
                text
            });
        },
    
        confirm: ({title = "Are you sure?", text = "", confirmText = "Yes", cancelText = "Cancel"}) => {
            return Swal.fire({
                icon: "warning",
                title,
                text,
                showCancelButton: true,
                confirmButtonText: confirmText,
                cancelButtonText: cancelText
            });
        }
    },

    // ========================
    // Notifications
    // ========================
    notif(message, type = 'success', form = null) {
        const notif = document.getElementById('notification');
        if (!notif) return;

        const wrap = (msg, color) =>
            `<div class="notif-${color}">${msg}</div>`;

        if (type === 'success') {
            notif.innerHTML = wrap(message, 'green');
        } else {
            const errors = Array.isArray(message) ? message : [{ msg: message }];
            let hasFieldError = false;

            if (form) {
                errors.forEach(({ field, msg }) => {
                    const input = field ? form.querySelector(`[name="${field}"]`) : null;
                    if (input && input.nextElementSibling) {
                        input.nextElementSibling.textContent = msg;
                        hasFieldError = true;
                    }
                });
            }

            if (!hasFieldError) {
                const msgText = errors.map(e => e.msg).join('<br>');
                notif.innerHTML = wrap(msgText, 'red');
            }
        }

        setTimeout(() => notif.innerHTML = '', 3000);
    },    
};

window.Arc = Arc;
export default Arc;
