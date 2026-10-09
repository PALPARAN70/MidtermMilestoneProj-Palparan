/* Kusina ng Barangay — front-end behaviour
   1. Form validation that mirrors the PHP rules
   2. Dynamic ingredient rows
   3. Favorites without page reload (fetch + JSON)
   4. Small UI helpers: mobile nav, confirm dialogs, character counters */
(function () {
    'use strict';

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    /* ---------- 1. Validation ---------- */

    function labelText(field) {
        const label = field.id && document.querySelector('label[for="' + field.id + '"]');
        return label ? label.textContent.replace(/\(.*?\)/g, '').trim() : 'This field';
    }

    function errorBoxFor(field) {
        return field.closest('.field')?.querySelector('.error') ?? null;
    }

    function setError(field, msg) {
        const box = errorBoxFor(field);
        if (box) box.textContent = msg;
        field.classList.toggle('is-invalid', msg !== '');
        field.setAttribute('aria-invalid', msg ? 'true' : 'false');
    }

    function checkField(field, form) {
        if (!field.closest('.field') || field.disabled || field.type === 'hidden') return true;

        const raw = field.value;
        const value = field.type === 'password' ? raw : raw.trim();
        let msg = '';

        if (field.required && value === '') {
            msg = field.tagName === 'SELECT' ? 'Please choose a ' + labelText(field).toLowerCase() + '.'
                                             : labelText(field) + ' is required.';
        } else if (value !== '') {
            if (field.type === 'number') {
                const n = Number(value), min = Number(field.min), max = Number(field.max);
                if (!Number.isInteger(n) || n < min || n > max) {
                    msg = labelText(field) + ' must be a whole number from ' + min + ' to ' + max + '.';
                }
            } else if (field.minLength > 0 && value.length < field.minLength) {
                msg = labelText(field) + ' must be at least ' + field.minLength + ' characters.';
            } else if (field.maxLength > 0 && value.length > field.maxLength) {
                msg = labelText(field) + ' must be ' + field.maxLength + ' characters or fewer.';
            } else if (field.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(value)) {
                msg = 'Enter a valid email address.';
            } else if (field.pattern && !new RegExp('^(?:' + field.pattern + ')$', 'u').test(value)) {
                msg = field.dataset.patternMsg || 'Please match the requested format.';
            } else if (field.dataset.match) {
                const other = form.querySelector('[name="' + field.dataset.match + '"]');
                if (other && other.value !== raw) msg = field.dataset.matchMsg || 'Values do not match.';
            }
        }
        setError(field, msg);
        return msg === '';
    }

    /** Recipe form: at least one ingredient, and no amount without a name. */
    function checkIngredients(form) {
        const box = form.querySelector('#ingredients-error');
        if (!box) return true;
        let filled = 0, msg = '';
        form.querySelectorAll('.ingredient-row').forEach(function (row, i) {
            const qty = row.querySelector('[name="ingredient_qty[]"]');
            const name = row.querySelector('[name="ingredient_name[]"]');
            const q = qty.value.trim(), n = name.value.trim();
            name.classList.remove('is-invalid');
            if (q === '' && n === '') return;
            filled++;
            if (n === '' && !msg) {
                msg = 'Ingredient row ' + (i + 1) + ' has an amount but no ingredient name.';
                name.classList.add('is-invalid');
            }
        });
        if (!msg && filled === 0) msg = 'Add at least one ingredient.';
        box.textContent = msg;
        return msg === '';
    }

    function wireValidation(form) {
        form.noValidate = true;
        const fields = () => form.querySelectorAll('.field input, .field textarea, .field select');

        form.addEventListener('input', function (ev) {
            const f = ev.target;
            if (f.classList.contains('is-invalid') && f.closest('.field')) checkField(f, form);
            if (f.closest('.ingredient-row') && form.querySelector('#ingredients-error')?.textContent) {
                checkIngredients(form);
            }
        });
        form.addEventListener('focusout', function (ev) {
            const f = ev.target;
            if (f.closest('.field') && f.value !== '') checkField(f, form);
        });
        form.addEventListener('submit', function (ev) {
            let ok = true;
            fields().forEach(function (f) { if (!checkField(f, form)) ok = false; });
            if (!checkIngredients(form)) ok = false;
            if (!ok) {
                ev.preventDefault();
                form.querySelector('.is-invalid')?.focus();
            }
        });
    }

    /* ---------- 2. Ingredient rows ---------- */

    function wireIngredients() {
        const list = document.getElementById('ingredient-rows');
        const tpl = document.getElementById('ingredient-template');
        const addBtn = document.getElementById('add-ingredient');
        if (!list || !tpl || !addBtn) return;
        const max = Number(list.dataset.max) || 40;

        function refresh() {
            const count = list.children.length;
            addBtn.disabled = count >= max;
            addBtn.textContent = count >= max ? 'Ingredient limit reached' : '+ Add ingredient';
            list.querySelectorAll('[data-remove-row]').forEach(b => { b.disabled = count === 1; });
        }

        addBtn.addEventListener('click', function () {
            if (list.children.length >= max) return;
            list.appendChild(tpl.content.cloneNode(true));
            list.lastElementChild.querySelector('[name="ingredient_qty[]"]').focus();
            refresh();
        });

        list.addEventListener('click', function (ev) {
            const btn = ev.target.closest('[data-remove-row]');
            if (!btn || list.children.length === 1) return;
            btn.closest('.ingredient-row').remove();
            refresh();
        });

        // Enter in the last ingredient name adds a new row instead of submitting
        list.addEventListener('keydown', function (ev) {
            if (ev.key === 'Enter' && ev.target.name === 'ingredient_name[]') {
                ev.preventDefault();
                if (ev.target.closest('.ingredient-row') === list.lastElementChild) addBtn.click();
            }
        });
        refresh();
    }

    /* ---------- 3. Favorites (AJAX) ---------- */

    function paintFavorite(btn, saved, count) {
        btn.setAttribute('aria-pressed', saved ? 'true' : 'false');
        btn.title = saved ? 'Remove from favorites' : 'Save to favorites';
        btn.querySelector('.fav-count').textContent = count;
        btn.querySelector('.fav-label').textContent = saved ? 'Saved' : 'Save';
    }

    function toast(message) {
        let el = document.querySelector('.toast');
        if (!el) {
            el = document.createElement('div');
            el.className = 'toast';
            el.setAttribute('role', 'status');
            document.body.appendChild(el);
        }
        el.textContent = message;
        el.classList.add('show');
        clearTimeout(el._t);
        el._t = setTimeout(() => el.classList.remove('show'), 2200);
    }

    document.addEventListener('click', async function (ev) {
        const btn = ev.target.closest('.fav-btn');
        if (!btn || btn.dataset.busy) return;
        btn.dataset.busy = '1';
        const id = btn.dataset.recipe;

        try {
            const res = await fetch('api/favorite.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': csrf },
                body: new URLSearchParams({ recipe_id: id }),
                credentials: 'same-origin'
            });
            const data = await res.json();
            if (!res.ok || !data.ok) throw new Error(data.message || 'Something went wrong.');

            document.querySelectorAll('.fav-btn[data-recipe="' + id + '"]')
                .forEach(b => paintFavorite(b, data.favorited, data.count));
            toast(data.favorited ? 'Saved to favorites ♥' : 'Removed from favorites');

            // On the favorites page, take the card away when un-saved
            const grid = btn.closest('[data-remove-unfavorited]');
            if (grid && !data.favorited) {
                btn.closest('.recipe-card').remove();
                if (!grid.children.length) document.getElementById('favorites-empty')?.removeAttribute('hidden');
            }
        } catch (err) {
            toast(err.message || 'Could not reach the server.');
        } finally {
            delete btn.dataset.busy;
        }
    });

    /* ---------- 4. UI helpers ---------- */

    function wireCounters() {
        document.querySelectorAll('textarea[data-counter][maxlength]').forEach(function (ta) {
            const out = document.createElement('p');
            out.className = 'counter';
            ta.insertAdjacentElement('afterend', out);
            const update = () => {
                const left = ta.maxLength - ta.value.length;
                out.textContent = left + ' characters left';
                out.classList.toggle('low', left < 50);
            };
            ta.addEventListener('input', update);
            update();
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('form[data-validate]').forEach(wireValidation);
        wireIngredients();
        wireCounters();

        document.querySelectorAll('form[data-confirm]').forEach(function (form) {
            form.addEventListener('submit', function (ev) {
                if (!window.confirm(form.dataset.confirm)) ev.preventDefault();
            });
        });

        const toggle = document.querySelector('.nav-toggle');
        const nav = document.getElementById('site-nav');
        if (toggle && nav) {
            toggle.addEventListener('click', function () {
                const open = nav.classList.toggle('open');
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
        }
    });
})();
