var okeyEmitUrl = null;
var okeyCancel = false;
var loader = document.getElementById('eokey_loader');

if(document.querySelector('.okey-modal-wrapper') !== null) {
    class OkeyCustomAdditionalFieldsModal {
        el = null;
        button = null;
        ok = null;
        cancel = null;
        okeyIsPersoanaFizica = null;
        okeyIsClient = null;
        okeyCuiInput = null;
        okeyCnpInput = null;
        formSubmit = null;

        constructor()
        {
            this.button = document.querySelector('.okey-custom-modal');
            this.el = document.querySelector('.okey-modal-wrapper');
            this.ok = document.querySelector('.okey-modal-ok');
            this.cancel = document.querySelector('.okey-modal-cancel');
            this.okeyIsPersoanaFizica = document.querySelector('#okeyIsPersoanaFizica');
            this.okeyIsClient = document.querySelector('#okeyIsClient');
            this.formSubmit = document.querySelector('#post');
            this.okeyCuiInput = document.querySelector('#okeyCui');

            this.button.addEventListener('click', () => this.showModal());
            this.ok.addEventListener('click', () => this.clickOk());
            this.cancel.addEventListener('click', () => this.clickCancel());
            this.okeyIsPersoanaFizica.addEventListener('click', () => this.setCNP());
            this.okeyIsClient.addEventListener('click', () => this.setCUI());
            this.formSubmit.setAttribute("onsubmit", "return waitOkeyAdditionalData()");
        }

        showModal()
        {
            this.el.style.display = "block";
            this.button.setAttribute("data-processing", true);
        }

        hideModal()
        {
            this.el.style.display = "none";
        }

        clickOk()
        {
            var href = this.button.getAttribute('data-url');
            if (this.okeyIsPersoanaFizica.checked) {
                okeyEmitUrl = href + '&isPf=1&cui=';
            } else if (this.okeyIsClient.checked) {
                okeyEmitUrl = href + '&isPf=0&cui=' + this.okeyCuiInput.value;
            }
        }

        setCUI()
        {
            if (this.okeyIsClient.checked) {
                document.querySelector('.okeyCuiContent').style.display = "block";
                this.okeyIsPersoanaFizica.checked = false;
            } else {
                document.querySelector('.okeyCuiContent').style.display = "none";
            }
        }

        setCNP()
        {
            if (this.okeyIsPersoanaFizica.checked) {
                document.querySelector('.okeyCuiContent').style.display = "none";
                this.okeyIsClient.checked = false;
            }
        }

        clickCancel()
        {
            okeyCancel = true;
            this.okeyIsClient.checked = false;
            this.okeyIsPersoanaFizica.checked = false;
            this.button.setAttribute("data-processing", false);
            document.querySelector('.okeyCuiContent').style.display = "none";
            document.querySelector('#okeyCui').value = "";
            this.hideModal();
        }

    }

    new OkeyCustomAdditionalFieldsModal();
}

function waitOkeyAdditionalData()
{
    var isOkeyAdditionalDataProcessingStarted = document.querySelector('.okey-custom-modal').getAttribute("data-processing");
    if(isOkeyAdditionalDataProcessingStarted === 'true') {
        if(okeyEmitUrl !== null) {
            window.location.href = okeyEmitUrl;
            document.querySelector('.okey-custom-modal').setAttribute("data-processing", 'false');
            okeyEmitUrl = null;
            showLoading();
            return false;
        } else {
            return false;
        }
    } else {
        if(okeyCancel) {
            okeyCancel = false;
            return false;
        }
        else { return true;
        }
    }
}

function showLoading()
{
    loader.style.visibility = 'visible';
}

