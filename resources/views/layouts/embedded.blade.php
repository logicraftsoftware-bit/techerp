<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>New Customer</title>
@vite(['resources/css/app.css', 'resources/js/app.js'])
</head><body class="bg-slate-50 p-4 text-slate-700">
<div id="customer-errors" role="alert" class="mb-4 whitespace-pre-line text-rose-600" hidden></div>
@yield('content')
@stack('scripts')
<script>
const form = document.querySelector('form');
form.querySelector('a.btn-secondary').addEventListener('click', event => { event.preventDefault(); window.parent.dispatchEvent(new CustomEvent('close-customer')); });
form.addEventListener('submit', async event => {
 event.preventDefault();
 const buttons = form.querySelectorAll('button:not([type="button"])');
 buttons.forEach(button => button.disabled = true);
 const errors = document.getElementById('customer-errors'); errors.hidden = true;
 try {
  const response = await fetch(form.action, {method: 'POST', body: new FormData(form), headers: {'Accept': 'application/json'}});
  const data = await response.json();
  if (!response.ok) throw new Error(Object.values(data.errors || {}).flat().join('\n') || data.message || 'Unable to create customer.');
  window.parent.dispatchEvent(new CustomEvent('customer-created', {detail: data}));
  window.location.reload();
 } catch (error) { errors.textContent = error.message; errors.hidden = false; window.scrollTo(0, 0); }
 finally { buttons.forEach(button => button.disabled = false); }
});
</script></body></html>
