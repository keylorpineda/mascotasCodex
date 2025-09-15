const init_custom_files = () => {
	const FIND_FILES_CONTAINER = document.querySelectorAll(`[data-app-files-find]`);
	return FIND_FILES_CONTAINER.forEach((c) => {
		c.onclick = (ev) => {
			const INPUT_FILE = c.querySelector(`input[type="file"]`);
			const INPUT_TEXT = c.querySelector(`input[type="text"]`);
			INPUT_FILE.onchange = () => {
				INPUT_TEXT.value = "";
				if (INPUT_FILE.files[0]) {
					INPUT_TEXT.value = INPUT_FILE.files[0].name;
				}
			}
			INPUT_FILE.click();
		}
	});
}
window.addEventListener("load", init_custom_files);