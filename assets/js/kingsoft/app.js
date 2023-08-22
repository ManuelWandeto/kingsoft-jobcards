
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

function illustrateError(errorSlotId, illustrationPath, message) {
    document.getElementById(errorSlotId).classList.toggle('show')
    const errorIllustraion = document.querySelector(`#${errorSlotId} img.error-illustration`)
    const errorDescription = document.querySelector(`#${errorSlotId} span.error-description`)
    errorIllustraion.setAttribute('src', illustrationPath)
    errorDescription.textContent = message
}