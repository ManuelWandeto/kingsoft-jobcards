function returnFileSize(number) {
    if (number < 1024) {
        return `${number} bytes`;
    } else if (number >= 1024 && number < 1048576) {
        return `${(number / 1024).toFixed(1)} KB`;
    } else if (number >= 1048576) {
        return `${(number / 1048576).toFixed(1)} MB`;
    }
}
function shortenFileName(filename, size = 15) {
    let portions = filename.split('.')
    if (portions[0].length <= size) {
        return portions.join('.')
    }
    return [portions[0].slice(0, size) + '---' , portions.pop()].join('.');
}
function removeFileFromFileList(filename, inputId) {
    const dt = new DataTransfer()
    const input = document.getElementById(inputId)
    const { files } = input
    
    for (let i = 0; i < files.length; i++) {
      const file = files[i]
      if (filename !== file.name)
        dt.items.add(file) // here you exclude the file. thus removing it.
    }
    
    input.files = dt.files // Assign the updates list
}
async function deleteUploadedFile(path, jobId) {
    try {
        const res = await axios.delete('api/files/delete_file.php', {data: {filepath: path, jobId}})
        if (!res.data) {
            throw new Error("unknown error occured")
        }
        showAlert('alert-success', 'Success!', 'Successfully deleted file')
        return true;
    } catch (e) {
        showAlert('alert-danger', 'Error occured', `Error deleting file: ${e}`, 3500)
        return false;
    }
}
