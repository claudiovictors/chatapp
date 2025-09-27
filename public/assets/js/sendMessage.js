const inputMessage = document.querySelector("#message")
const btnSubmit = document.querySelector("#btnSubmit")

inputMessage.addEventListener('keyup', () => {
    if (inputMessage.value == '' || inputMessage.value == null) {
        btnSubmit.classList.remove('active')
    } else {
        btnSubmit.classList.add('active')
    }
})