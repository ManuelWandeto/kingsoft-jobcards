// import {computePosition} from 'https://cdn.jsdelivr.net/npm/@floating-ui/dom@1.5.1/+esm';
function clearFormErrors(fields) {
    Object.values(fields).forEach(field => {
        if (field?.error) {
            field.error = null
        }
    })
}

function showAlert(className, heading, message, duration=2500) {
    let slot = document.getElementById('alert-slot')
    slot.innerHTML = `
    <div class="alert fade show ${className}" role="alert">
        <h4 class="mt-0 alert-heading">${heading}</h4>
        ${message}
    </div>
    `
    setTimeout(() => {
        $('.alert').alert('close')
    }, duration);
}


document.addEventListener('alpine:init', ()=> {

    Alpine.plugin(intersect)
})