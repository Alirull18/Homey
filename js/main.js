
function validateSignupForm() {
    var form = document.forms["signupForm"];
    if (!form) return true;

    var fullName = form["full_name"].value;
    var email = form["email"].value;
    var password = form["password"].value;
    var confirmPassword = form["confirm_password"].value;

    if (fullName.trim() === "") {
        alert("Full Name must be filled out.");
        return false;
    }
    if (email.trim() === "") {
        alert("Email must be filled out.");
        return false;
    }
    if (password.length < 6) {
        alert("Password must be at least 6 characters long.");
        return false;
    }
    if (password !== confirmPassword) {
        alert("Passwords do not match.");
        return false;
    }
    return true;
}

function validateProfileForm() {
    var form = document.forms["profileForm"];
    if (!form) return true;

    var age = parseInt(form["age"].value);
    var weight = parseFloat(form["weight"].value);
    var height = parseFloat(form["height"].value);

    if (isNaN(age) || age < 1 || age > 120) {
        alert("Please enter a valid age between 1 and 120.");
        return false;
    }
    if (isNaN(weight) || weight <= 0) {
        alert("Please enter a valid weight.");
        return false;
    }
    if (isNaN(height) || height <= 0) {
        alert("Please enter a valid height.");
        return false;
    }
    return true;
}
