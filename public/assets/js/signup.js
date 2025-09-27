const inputField = document.querySelector("#password")
const btnView = document.querySelector(".bx-show")

btnView.addEventListener("click", function() {
    if(inputField.type == "password"){
        inputField.type = "text"
        btnView.style.color = "#333"
    }else {
        inputField.type = "password"
        btnView.style.color = "#aaa"
    }
})