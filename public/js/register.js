var validateAlias = function(nolookup)
{
	var checkAlias = function(aliasResultText) {
		var aliasResult = eval("[" + aliasResultText + "]");
		
		if (!aliasResult[0]["valid"]) {
			errorBox.style.display = "block";
			errorBox.innerHTML = aliasResult[0]["error"];
			alias.style.backgroundColor = "#faa";
			return false;
		}
		
		errorBox.style.display = "none";
		alias.style.backgroundColor = "#fff";
		button.disabled = false;
		
		done = true;
	}

	var done = true;
	var errorBox = document.getElementById("aliasError");
	var alias = document.getElementById("alias");
	if (alias.value.search(/[<>&]+/i) > -1) {
		errorBox.style.display = "block";
		errorBox.innerHTML = "Ditt användarnamn får inte innehålla <, > eller &.";
		alias.style.backgroundColor = "#faa";
		return 1;
	}
	else if (alias.value.length < 3 || alias.value.length > 20) {
		errorBox.style.display = "block";
		errorBox.innerHTML = "Ditt användarnamn får inte vara kortare än 3 tecken eller längre än 20 tecken.";
		alias.style.backgroundColor = "#faa";
		return 1;
	}
	else if (nolookup) {
		if (!done) {
			return false;
		}
		
		done = false;
		setTimeout(
			function() {
				var aliasResultText = $.get("/register/userinfo/lookupalias?alias=" + encodeURI(alias.value), checkAlias);
			}, 
			1000);
	}
	
	return 0;
}

var validatePassword = function()
{
	var errorBox = document.getElementById("passwordError");
	var pass = document.getElementById("pass");
	var passRepeat = document.getElementById("passvalid");
	
	if (pass.value.length < 6) {
		errorBox.style.display = "block";
		errorBox.innerHTML = "Ditt lösenord måste vara minst 6 tecken långt.";
		pass.style.backgroundColor = "#faa";
		return 1;
	}
	
	pass.style.backgroundColor = "#fff";
	errorBox.style.display = "none";
	
	if (passRepeat.value != pass.value) {
		errorBox.style.display = "block";
		errorBox.innerHTML = "Lösenorden överensstämmer inte.";
		pass.style.backgroundColor = "#faa";
		passRepeat.style.backgroundColor = "#faa";
		return 2;
	}
	
	passRepeat.style.backgroundColor = "#fff";
	errorBox.style.display = "none";
	
	return 0;
}

var validateEmail = function()
{
	var errorBox = document.getElementById("emailError");
	var email = document.getElementById("email");
	
	var emailRegex = /^[A-Z0-9._%+-]+@(?:[A-Z0-9-]+\.)+(?:[A-Z]{2}|com|org|net|gov|mil|biz|info|mobi|name|aero|jobs|museum)$/i;
	if (email.value.search(emailRegex) == -1) {
		errorBox.style.display = "block";
		errorBox.innerHTML = "Din e-post är inte giltig.";
		email.style.backgroundColor = "#faa";
		return 1;
	}
	
	email.style.backgroundColor = "#fff";
	errorBox.style.display = "none";
	
	return 0;
}

var validateBirthdate = function()
{
	var errorBox = document.getElementById("birthdateError");
	var birthdate = document.getElementById("birthdate");
	var gender = document.getElementById("gender");
	
	var ssnRegex = /^((19|20)(\d{2}))(\d{2})(\d{2})-(\d{4})$/i;
	if (birthdate.value.search(ssnRegex) == -1) {
		errorBox.style.display = "block";
		errorBox.innerHTML = "Ditt personnummer är inte giltigt.";
		birthdate.style.backgroundColor = "#faa";
		return 1;
	}
	
	//$digits = sprintf("%02d%02d%02d%04d", $shortYear, $month, $day, $digits);
	var digits = birthdate.value.slice(2,8) + birthdate.value.slice(9,13);
	
	var str = "";
	for (var i = 0; i < digits.length; i++) {
		var n = parseInt(digits.charAt(i)) * (i % 2 == 1 ? 1 : 2);
		str += n;
	}
	
	var sum = 0;
	for (var i = 0; i < str.length; i++) {
		sum += parseInt(str.charAt(i));
	}
	
	if (sum % 10 > 0) {
		errorBox.style.display = "block";
		errorBox.innerHTML = "Ditt personnummer är inte giltigt.";
		birthdate.style.backgroundColor = "#faa";
		return 1;
	}
	
	var genderDigit = parseInt(digits.charAt(8));
	if ((genderDigit%2) != gender.selectedIndex) {
		errorBox.style.display = "block";
		errorBox.innerHTML = "Ditt personnummer är inte giltigt.";
		birthdate.style.backgroundColor = "#faa";
		return 2;
	}
	
	birthdate.style.backgroundColor = "#fff";
	errorBox.style.display = "none";
	
	return 0;
}

var validateName = function()
{
	var errorBox = document.getElementById("nameError");
	var name = document.getElementById("name");
	var surname = document.getElementById("surname");
	
	if (name.value.length < 2) {
		errorBox.style.display = "block";
		errorBox.innerHTML = "Ditt namn är för kort.";
		name.style.backgroundColor = "#faa";
		return 1;
	}
	
	name.style.backgroundColor = "#fff";
	errorBox.style.display = "none";
	
	if (surname.value.length < 2) {
		errorBox.style.display = "block";
		errorBox.innerHTML = "Ditt efternamn är för kort.";
		surname.style.backgroundColor = "#faa";
		return 2;
	}
	
	surname.style.backgroundColor = "#fff";
	errorBox.style.display = "none";
	
	return 0;
}

var validateAddress = function()
{
	var errorBox = document.getElementById("addressError");
	var address = document.getElementById("address");
	var zipcode = document.getElementById("zipcode");
	var city = document.getElementById("city");
	var country = document.getElementById("country");
	
	if (address.value.length < 2) {
		errorBox.style.display = "block";
		errorBox.innerHTML = "Din adress är för kort.";
		address.style.backgroundColor = "#faa";
		return 1;
	}
	
	address.style.backgroundColor = "#fff";
	errorBox.style.display = "none";
	
	if (zipcode.value.search(/\d{5}/) == -1) {
		errorBox.style.display = "block";
		errorBox.innerHTML = "Ditt postnummer är inte giltigt.";
		zipcode.style.backgroundColor = "#faa";
		return 2;
	}
	
	zipcode.style.backgroundColor = "#fff";
	errorBox.style.display = "none";
	
	if (city.value.length < 2) {
		errorBox.style.display = "block";
		errorBox.innerHTML = "Din ort är för kort.";
		city.style.backgroundColor = "#faa";
		return 3;
	}
	
	city.style.backgroundColor = "#fff";
	errorBox.style.display = "none";
	
	return 0;
}

var validatePhoneNumber = function()
{
	var errorBox = document.getElementById("phoneError");
	var phone = document.getElementById("phone");
	
	if (phone.value.search(/\d{2,4}-\d{5,8}/) == -1) {
		errorBox.style.display = "block";
		errorBox.innerHTML = "Ditt telefon-nummer är inte giltigt.";
		phone.style.backgroundColor = "#faa";
		return 1;
	}
	
	phone.style.backgroundColor = "#fff";
	errorBox.style.display = "none";
	
	return 0;
}

var validateRegisterForm = function()
{
	var regForm = document.getElementById("regForm");
	var button = document.getElementById("nextStep");
	button.disabled = true;

	// Validate alias
	var alias = document.getElementById("alias");
	var aliasErrorBox = document.getElementById("aliasError");
	if (validateAlias(true) > 0) {
		alias.focus();
		button.disabled = false;
		return false;
	}
	
	var aliasResultText = $.ajax({url: "/register/userinfo/lookupalias?alias=" + encodeURI(alias.value),
		async: false}).responseText;
		
	var aliasResult = eval("[" + aliasResultText + "]");
	if (!aliasResult[0]["valid"]) {
		aliasErrorBox.style.display = "block";
		aliasErrorBox.innerHTML = aliasResult[0]["error"];
		alias.style.backgroundColor = "#faa";
		alias.focus();
		button.disabled = false;
		return false;
	}
	
	aliasErrorBox.style.display = 'none';
	alias.style.backgroundColor = "#fff";
	
	// Validate password
	var pass = document.getElementById("pass");
	var passRepeat = document.getElementById("passvalid");
	
	var passwordResult = validatePassword();
	if (passwordResult == 1) {
		pass.focus();
		button.disabled = false;
		return false;
	}
	else if (passwordResult == 2) {
		passRepeat.focus();
		button.disabled = false;
		return false;
	}
	
	// Validate email
	var email = document.getElementById("email");
	if (validateEmail() > 0) {
		email.focus();
		button.disabled = false;
		return false;
	}
	
	// Validate birthdate
	var birthdate = document.getElementById("birthdate");
	if (validateBirthdate() > 0) {
		birthdate.focus();
		button.disabled = false;
		return false;
	}
	
	// Validate name
	var name = document.getElementById("name");
	var surname = document.getElementById("surname");
	
	var nameResult = validateName();
	if (nameResult == 1) {
		name.focus();
		button.disabled = false;
		return false;
	}
	else if (nameResult == 2) {
		surname.focus();
		button.disabled = false;
		return false;
	}
	
	// Validate address
	var address = document.getElementById("address");
	var zipcode = document.getElementById("zipcode");
	var city = document.getElementById("city");
	
	var addressResult = validateAddress();
	if (addressResult == 1) {
		address.focus();
		button.disabled = false;
		return false;
	}
	else if (addressResult == 2) {
		zipcode.focus();
		button.disabled = false;
		return false;
	}
	else if (addressResult == 3) {
		city.focus();
		button.disabled = false;
		return false;
	}
	
	// Validate phone number
	var phone = document.getElementById("phone");
	if (validatePhoneNumber() > 0) {
		phone.focus();
		button.disabled = false;
		return false;
	}
	
	return true;
}

$(document).ready(function() {
	document.getElementById("alias").onkeyup = validateAlias;
	document.getElementById("pass").onkeyup = validatePassword;
	document.getElementById("passvalid").onkeyup = validatePassword;
	document.getElementById("email").onkeyup = validateEmail;
	document.getElementById("birthdate").onkeyup = validateBirthdate;
	document.getElementById("gender").onchange = validateBirthdate;
	document.getElementById("name").onkeyup = validateName;
	document.getElementById("surname").onkeyup = validateName;
	document.getElementById("address").onkeyup = validateAddress;
	document.getElementById("zipcode").onkeyup = validateAddress;
	document.getElementById("city").onkeyup = validateAddress;
	document.getElementById("phone").onkeyup = validatePhoneNumber;
	document.getElementById("regForm").onsubmit = validateRegisterForm;
})