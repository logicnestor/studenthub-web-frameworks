
const API_URL = '../php/api/events.php';

const form = document.getElementById('event-form');
const messageBox = document.getElementById('message');
const tableBody = document.getElementById('events-table');
const loading = document.getElementById('loading');
const saveButton = document.getElementById('save-button');
const cancelEditButton = document.getElementById('cancel-edit');

let events = [];
let csrfToken = '';
let searchTimer = null;
let requestSequence = 0;

function showMessage(message, type = 'success') {
    messageBox.textContent = message;
    messageBox.className = `alert alert-${type}`;
    messageBox.style.display = 'block';
}

function hideMessage() {
    messageBox.style.display = 'none';
    messageBox.textContent = '';
}

async function apiRequest(url, options = {}) {
    const response = await fetch(url, {
        credentials: 'same-origin',
        ...options
    });

    const contentType = response.headers.get('content-type') || '';

    if (!contentType.includes('application/json')) {
        throw new Error(
            response.status === 401 || response.status === 403
                ? 'Your session may have expired or you lack permission. Log in again.'
                : 'The server did not return JSON. Check your PHP endpoint and authentication.'
        );
    }

    const result = await response.json();

    if (!response.ok || result.success !== true) {
        throw new Error(result.message || `Request failed (${response.status}).`);
    }

    return result;
}

async function loadCsrfToken() {
    const result = await apiRequest(`${API_URL}?action=csrf`);
    csrfToken = result.csrf_token;
}

function addCell(row, value) {
    const cell = document.createElement('td');
    cell.textContent = value ?? '';
    row.appendChild(cell);
    return cell;
}

function renderEvents(items) {
    tableBody.replaceChildren();

    if (!items.length) {
        const row = document.createElement('tr');
        const cell = document.createElement('td');
        cell.colSpan = 7;
        cell.className = 'text-center text-secondary py-4';
        cell.textContent = 'No events found.';
        row.appendChild(cell);
        tableBody.appendChild(row);
        return;
    }

    for (const event of items) {
        const row = document.createElement('tr');

        addCell(row, event.event_id);
        addCell(row, event.title);
        addCell(row, event.category);
        addCell(row, event.event_date);
        addCell(row, event.location);

        const statusCell = document.createElement('td');
        const badge = document.createElement('span');
        badge.className = `badge ${
            event.status === 'open' ? 'text-bg-success' : 'text-bg-secondary'
        }`;
        badge.textContent = event.status;
        statusCell.appendChild(badge);
        row.appendChild(statusCell);

        const actions = addCell(row, '');
        actions.className = 'd-flex gap-1';

        const editButton = document.createElement('button');
        editButton.type = 'button';
        editButton.className = 'btn btn-sm btn-outline-primary';
        editButton.textContent = 'Edit';
        editButton.addEventListener('click', () => startEdit(event));

        const deleteButton = document.createElement('button');
        deleteButton.type = 'button';
        deleteButton.className = 'btn btn-sm btn-outline-danger';
        deleteButton.textContent = 'Delete';
        deleteButton.addEventListener('click', () => deleteEvent(event));

        actions.append(editButton, deleteButton);
        tableBody.appendChild(row);
    }
}

function updateCategoryOptions(items) {
    const select = document.getElementById('category-filter');
    const selected = select.value;
    const categories = [...new Set(items.map(event => event.category))].sort();

    select.replaceChildren(new Option('All categories', ''));

    for (const category of categories) {
        select.add(new Option(category, category));
    }

    if (categories.includes(selected)) {
        select.value = selected;
    }
}

async function loadEvents() {
    const sequence = ++requestSequence;
    loading.hidden = false;

    const params = new URLSearchParams();
    const query = document.getElementById('search').value.trim();
    const category = document.getElementById('category-filter').value;
    const status = document.getElementById('status-filter').value;

    if (query) params.set('q', query);
    if (category) params.set('category', category);
    if (status) params.set('status', status);

    try {
        const result = await apiRequest(`${API_URL}?${params.toString()}`);

        // Ignore older responses if a more recent search has completed.
        if (sequence !== requestSequence) return;

        events = result.data;
        renderEvents(events);
        updateCategoryOptions(events);
        hideMessage();
    } catch (error) {
        if (sequence === requestSequence) {
            showMessage(error.message, 'danger');
            tableBody.replaceChildren();
        }
    } finally {
        if (sequence === requestSequence) {
            loading.hidden = true;
        }
    }
}

function startEdit(event) {
    document.getElementById('event-id').value = event.event_id;
    document.getElementById('title').value = event.title;
    document.getElementById('category').value = event.category;
    document.getElementById('event-date').value = event.event_date;
    document.getElementById('location').value = event.location;
    document.getElementById('description').value = event.description;
    document.getElementById('event-status').value = event.status;

    document.getElementById('form-heading').textContent = 'Edit Event';
    saveButton.textContent = 'Update Event';
    cancelEditButton.hidden = false;

    form.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function resetForm() {
    form.reset();
    document.getElementById('event-id').value = '';
    document.getElementById('form-heading').textContent = 'Add Event';
    saveButton.textContent = 'Save Event';
    cancelEditButton.hidden = true;
}

form.addEventListener('submit', async event => {
    event.preventDefault();
    hideMessage();

    if (!form.reportValidity()) return;

    const id = document.getElementById('event-id').value;

    const payload = {
        action: id ? 'update' : 'create',
        title: document.getElementById('title').value.trim(),
        category: document.getElementById('category').value.trim(),
        event_date: document.getElementById('event-date').value,
        location: document.getElementById('location').value.trim(),
        description: document.getElementById('description').value.trim(),
        status: document.getElementById('event-status').value
    };

    if (id) payload.event_id = Number(id);

    saveButton.disabled = true;
    saveButton.textContent = 'Saving...';

    try {
        const result = await apiRequest(API_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify(payload)
        });

        resetForm();
        showMessage(result.message);
        await loadEvents();
    } catch (error) {
        showMessage(error.message, 'danger');
    } finally {
        saveButton.disabled = false;
        saveButton.textContent =
            document.getElementById('event-id').value ? 'Update Event' : 'Save Event';
    }
});

async function deleteEvent(event) {
    if (!confirm(`Delete "${event.title}"? This cannot be undone.`)) return;

    try {
        const result = await apiRequest(API_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({
                action: 'delete',
                event_id: event.event_id
            })
        });

        if (document.getElementById('event-id').value == event.event_id) {
            resetForm();
        }

        showMessage(result.message);
        await loadEvents();
    } catch (error) {
        showMessage(error.message, 'danger');
    }
}

cancelEditButton.addEventListener('click', resetForm);
document.getElementById('refresh-button').addEventListener('click', loadEvents);
document.getElementById('category-filter').addEventListener('change', loadEvents);
document.getElementById('status-filter').addEventListener('change', loadEvents);

document.getElementById('search').addEventListener('input', () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(loadEvents, 350);
});

(async function initialize() {
    try {
        await loadCsrfToken();
        await loadEvents();
    } catch (error) {
        showMessage(error.message, 'danger');
    }
})();
