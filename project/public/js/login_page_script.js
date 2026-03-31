function showForm(formId){
    document.querySelectorAll(".form-box").forEach(form => form.classList.remove("active")); //Look through all the forms in form-box, then remove all actives
    document.getElementById(formId).classList.add("active"); //Look at the form with formId, then make it active
}

