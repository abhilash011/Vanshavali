<!DOCTYPE html>
<!--
<?php
session_start();
if (!isset($_SESSION['user'])) {
  header('Location: login.php');
  exit();
}
?>
-->
<html>
<head>
  <title>Register or Edit Family Member</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    /* Custom styles that don't conflict with Bootstrap */
    .searchable-dropdown {
      position: relative;
    }
    .dropdown-list {
      position: absolute;
      width: 100%;
      max-height: 200px;
      overflow-y: auto;
      background: white;
      border: 1px solid #ced4da;
      border-radius: 0.375rem;
      z-index: 1000;
      display: none;
    }
    .dropdown-list.show {
      display: block;
    }
    .dropdown-item {
      padding: 8px;
      cursor: pointer;
    }
    .dropdown-item:hover {
      background-color: #f8f9fa;
    }
    .selected-items {
      margin-top: 5px;
    }
    .selected-tag {
      display: inline-block;
      background: #e9ecef;
      padding: 5px 10px;
      margin: 2px;
      border-radius: 20px;
    }
    .selected-tag span {
      margin-left: 5px;
      cursor: pointer;
      font-weight: bold;
    }
    .add-new-option {
      font-weight: bold;
      color: #28a745;
    }
    .image-preview {
      max-width: 100%;
      max-height: 200px;
      margin-top: 10px;
      border: 1px solid #dee2e6;
      border-radius: 0.375rem;
      display: none;
    }
    .current-image {
      max-width: 100%;
      max-height: 200px;
      margin-top: 10px;
      border: 1px solid #dee2e6;
      border-radius: 0.375rem;
    }
    .form-section {
      margin-bottom: 1.5rem;
    }
    #response {
      background: #f8f9fa;
      padding: 1rem;
      border-radius: 0.375rem;
    }
  </style>
  <!-- Add to head -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<style>
  body {
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(135deg, #fff8e1 0%, #fff3cd 100%);
  }
  .card-header {
    background: linear-gradient(to left, #f59e0b, #fcd34d) !important;
  }
  .btn-primary {
    background-color: #b8860b;
    border-color: #b8860b;
  }
</style>
</head>
<body class="bg-light">
    <!-- Add to top of body -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4">
  <div class="container">
    <a class="navbar-brand" href="index.php">
      <img src="https://pushkarajjyotishkaryalay.com/vanshavali/logo512.png" height="40" class="me-2">
      Sonar Samaj
    </a>
  </div>
</nav>
  <div class="container py-4">
    <div class="card shadow-sm">
      <div class="card-header bg-primary text-white">
        <h2 class="h4 mb-0"><i class="fas fa-user-edit me-2"></i>Add or Edit Family Member</h2>
      </div>
      <div class="card-body">
        <!-- Select existing member with search -->
        <div class="mb-4 form-section">
          <label class="form-label">Select Member to Edit (or leave blank to add new)</label>
          <div class="searchable-dropdown" id="member-select-dropdown">
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-search"></i></span>
              <input type="text" class="form-control" id="member-select-search" placeholder="Search for member to edit..." autocomplete="off">
            </div>
            <div class="dropdown-list" id="member-select-list"></div>
            <div class="selected-items mt-2" id="member-select-selected"></div>
            <input type="hidden" id="selected-member-id">
          </div>
        </div>

        <form id="memberForm">
          <!-- Basic Info -->
          <div class="row form-section">
            <div class="col-md-6 mb-3">
              <label for="name" class="form-label">Name</label>
              <input type="text" class="form-control" id="name" required>
            </div>
            <div class="col-md-6 mb-3">
              <label for="gender" class="form-label">Gender</label>
              <select class="form-select" id="gender">
                <option value="male">Male</option>
                <option value="female">Female</option>
              </select>
            </div>
            <div class="col-12 mb-3">
              <label for="relationship" class="form-label">Relationship</label>
              <input type="text" class="form-control" id="relationship">
            </div>
          </div>

          <!-- Parents -->
          <div class="row form-section">
            <div class="col-md-6 mb-3">
              <label class="form-label">Father</label>
              <div class="searchable-dropdown" id="father-dropdown">
                <div class="input-group">
                  <span class="input-group-text"><i class="fas fa-male"></i></span>
                  <input type="text" class="form-control" id="father-search" placeholder="Search for father..." autocomplete="off">
                </div>
                <div class="dropdown-list" id="father-list"></div>
                <div class="selected-items mt-2" id="father-selected"></div>
                <input type="hidden" id="fid-value">
              </div>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Mother</label>
              <div class="searchable-dropdown" id="mother-dropdown">
                <div class="input-group">
                  <span class="input-group-text"><i class="fas fa-female"></i></span>
                  <input type="text" class="form-control" id="mother-search" placeholder="Search for mother..." autocomplete="off">
                </div>
                <div class="dropdown-list" id="mother-list"></div>
                <div class="selected-items mt-2" id="mother-selected"></div>
                <input type="hidden" id="mid-value">
              </div>
            </div>
          </div>

          <!-- Partners -->
          <div class="form-section">
            <label class="form-label">Partners</label>
            <div class="searchable-dropdown" id="partners-dropdown">
              <div class="input-group">
                <span class="input-group-text"><i class="fas fa-heart"></i></span>
                <input type="text" class="form-control" id="partners-search" placeholder="Search for partners..." autocomplete="off">
              </div>
              <div class="dropdown-list" id="partners-list"></div>
              <div class="selected-items mt-2" id="partners-selected"></div>
              <input type="hidden" id="pids-value">
            </div>
          </div>

          <!-- Image -->
          <div class="form-section">
            <label class="form-label">Image</label>
            <div id="current-image-container" class="mb-2">
              <img id="current-image" class="current-image" src="" alt="Current image" style="display: none;">
              <small id="current-image-path" class="text-muted"></small>
            </div>
            <input type="file" class="form-control" id="image-upload" accept="image/*">
            <img id="image-preview" class="image-preview mt-2" alt="Image preview">
          </div>
          <input type="hidden" id="img">

          <!-- Dates -->
          <div class="row form-section">
            <div class="col-md-6 mb-3">
              <label for="bdate" class="form-label">Birth Year</label>
              <input type="number" class="form-control" id="bdate">
            </div>
            <div class="col-md-6 mb-3">
              <label for="ddate" class="form-label">Death Year</label>
              <input type="number" class="form-control" id="ddate">
            </div>
          </div>

          <!-- Additional Info -->
          <div class="row form-section">
            <div class="col-md-6 mb-3">
              <label for="tags" class="form-label">Tags (comma-separated)</label>
              <input type="text" class="form-control" id="tags" placeholder="e.g. main_male">
            </div>
            <div class="col-md-6 mb-3">
              <label for="orderId" class="form-label">Order ID</label>
              <input type="number" class="form-control" id="orderId">
            </div>
          </div>

          <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary" id="submitBtn">
              <i class="fas fa-save me-2"></i>Save Member
            </button>
          </div>
        </form>

        <div class="mt-4">
          <h3 class="h5">Saved Member Object:</h3>
          <pre id="response" class="p-3 bg-light border rounded"></pre>
        </div>
      </div>
    </div>
  </div>

<script>
let existingMembers = [];
let editingMemberId = null;
let selectedFatherId = null;
let selectedMotherId = null;
let selectedPartnerIds = [];
let imageFile = null;

// Add to your existing script
document.addEventListener('mousemove', function(e) {
  if (Date.now() - lastGlitter > 50) {
    const glitter = document.createElement('div');
    glitter.className = 'glitter';
    glitter.style.left = (e.pageX + (Math.random() * 15 - 7)) + 'px';
    glitter.style.top = (e.pageY + (Math.random() * 15 - 7)) + 'px';
    document.body.appendChild(glitter);
    setTimeout(() => glitter.remove(), 1200);
    lastGlitter = Date.now();
  }
});

// Initialize searchable dropdowns
function initializeSearchableDropdowns() {
  setupDropdown('member-select', false, true);
  setupDropdown('father', false);
  setupDropdown('mother', false);
  setupDropdown('partners', true);
  setupImageUpload();
}

function setupImageUpload() {
  const imageUpload = document.getElementById('image-upload');
  const imagePreview = document.getElementById('image-preview');
  
  imageUpload.addEventListener('change', function(e) {
    if (this.files && this.files[0]) {
      const file = this.files[0];
      imageFile = file;
      
      // Show preview
      const reader = new FileReader();
      reader.onload = function(e) {
        imagePreview.src = e.target.result;
        imagePreview.style.display = 'block';
      }
      reader.readAsDataURL(file);
    }
  });
}

function setupDropdown(type, multiple, includeAddNew = false) {
  const searchInput = document.getElementById(`${type}-search`);
  const dropdownList = document.getElementById(`${type}-list`);
  const selectedContainer = document.getElementById(`${type}-selected`);
  
  // Show dropdown list when focusing on search input
  searchInput.addEventListener('focus', () => {
    updateDropdownList(type, searchInput.value, includeAddNew);
    dropdownList.classList.add('show');
  });
  
  // Filter dropdown items based on search input
  searchInput.addEventListener('input', () => {
    updateDropdownList(type, searchInput.value, includeAddNew);
  });
  
  // Hide dropdown when clicking outside
  document.addEventListener('click', (e) => {
    if (!document.getElementById(`${type}-dropdown`).contains(e.target)) {
      dropdownList.classList.remove('show');
    }
  });
}

function updateDropdownList(type, searchTerm, includeAddNew = false) {
  const dropdownList = document.getElementById(`${type}-list`);
  
  // Different handling based on dropdown type
  const valueField = type === 'member-select' ? 'selected-member-id' : 
                     (type === 'partners' ? 'pids-value' : `${type.charAt(0)}id-value`);
  
  const genderFilter = type === 'father' ? 'male' : (type === 'mother' ? 'female' : null);
  
  // Clear the list
  dropdownList.innerHTML = '';
  
  // Add "Add New" option for member select
  if (type === 'member-select' && includeAddNew) {
    const addNewItem = document.createElement('div');
    addNewItem.classList.add('dropdown-item', 'add-new-option');
    addNewItem.textContent = '-- Add New Member --';
    addNewItem.addEventListener('click', () => {
      resetForm();
      dropdownList.classList.remove('show');
    });
    dropdownList.appendChild(addNewItem);
  }
  
  // Filter members based on search term and gender (for father/mother)
  const filteredMembers = existingMembers.filter(member => {
    // Skip self when editing (except for member select)
    if (type !== 'member-select' && editingMemberId && member.id == editingMemberId) return false;
    
    // Apply gender filter for father/mother
    if (genderFilter && member.gender !== genderFilter) return false;
    
    // Apply search filter
    if (searchTerm && !member.name.toLowerCase().includes(searchTerm.toLowerCase())) return false;
    
    // For partners, filter out already selected partners
    if (type === 'partners' && selectedPartnerIds.includes(member.id)) return false;
    
    return true;
  });
  
  // Add filtered members to dropdown
  filteredMembers.forEach(member => {
    const item = document.createElement('div');
    item.classList.add('dropdown-item');
    item.textContent = `${member.name} (ID: ${member.id})`;
    item.dataset.id = member.id;
    item.addEventListener('click', () => {
      if (type === 'member-select') {
        selectMemberToEdit(member);
      } else {
        selectMember(type, member);
        document.getElementById(`${type}-search`).value = '';
        if (type !== 'partners') {
          dropdownList.classList.remove('show');
        }
      }
      updateDropdownList(type, '', includeAddNew);
    });
    dropdownList.appendChild(item);
  });
  
  // Show "no results" if no matches
  if (filteredMembers.length === 0) {
    const noResults = document.createElement('div');
    noResults.classList.add('dropdown-item');
    noResults.textContent = 'No matching members found';
    noResults.style.fontStyle = 'italic';
    dropdownList.appendChild(noResults);
  }
}

function selectMemberToEdit(member) {
  // Clear previous selection
  const selectedContainer = document.getElementById('member-select-selected');
  selectedContainer.innerHTML = '';
  
  // Set the selected member
  editingMemberId = member.id;
  document.getElementById('selected-member-id').value = member.id;
  document.getElementById("submitBtn").textContent = "Update Member";
  
  // Add selected tag
  const tag = document.createElement('div');
  tag.classList.add('selected-tag');
  tag.innerHTML = `Editing: ${member.name} <span data-id="${member.id}">×</span>`;
  
  // Add remove functionality
  tag.querySelector('span').addEventListener('click', () => {
    resetForm();
  });
  
  selectedContainer.appendChild(tag);
  
  // Hide the dropdown
  document.getElementById('member-select-list').classList.remove('show');
  
  // Load member data
  loadMemberData(member);
}

function loadMemberData(member) {
  document.getElementById("name").value = member.name || "";
  document.getElementById("gender").value = member.gender || "male";
  document.getElementById("relationship").value = member.relationship || "";
  
  // Clear previous selections
  document.getElementById('father-selected').innerHTML = '';
  document.getElementById('mother-selected').innerHTML = '';
  document.getElementById('partners-selected').innerHTML = '';
  selectedFatherId = null;
  selectedMotherId = null;
  selectedPartnerIds = [];
  
  // Reset image preview
  document.getElementById('image-preview').style.display = 'none';
  document.getElementById('image-upload').value = '';
  imageFile = null;
  
  // Display current image if exists
  const currentImage = document.getElementById('current-image');
  const currentImagePath = document.getElementById('current-image-path');
  
  if (member.img) {
    currentImage.src = member.img;
    currentImage.style.display = 'block';
    currentImagePath.textContent = `Current image: ${member.img}`;
    document.getElementById('img').value = member.img;
  } else {
    currentImage.style.display = 'none';
    currentImagePath.textContent = '';
    document.getElementById('img').value = '';
  }
  
  // Set father if exists
  if (member.fid) {
    const father = existingMembers.find(m => m.id == member.fid);
    if (father) {
      selectedFatherId = father.id;
      document.getElementById('fid-value').value = father.id;
      addSelectedTag(document.getElementById('father-selected'), father, 'father');
    }
  }
  
  // Set mother if exists
  if (member.mid) {
    const mother = existingMembers.find(m => m.id == member.mid);
    if (mother) {
      selectedMotherId = mother.id;
      document.getElementById('mid-value').value = mother.id;
      addSelectedTag(document.getElementById('mother-selected'), mother, 'mother');
    }
  }
  
  // Set partners if exist
  if (member.pids && member.pids.length) {
    selectedPartnerIds = [...member.pids];
    document.getElementById('pids-value').value = selectedPartnerIds.join(',');
    member.pids.forEach(pid => {
      const partner = existingMembers.find(m => m.id == pid);
      if (partner) {
        addSelectedTag(document.getElementById('partners-selected'), partner, 'partners');
      }
    });
  }

  document.getElementById("bdate").value = member.bdate || "";
  document.getElementById("ddate").value = member.ddate || "";
  document.getElementById("tags").value = (member.tags || []).join(", ");
  document.getElementById("orderId").value = member.orderId || "";
}

function resetForm() {
  document.getElementById("memberForm").reset();
  editingMemberId = null;
  selectedFatherId = null;
  selectedMotherId = null;
  selectedPartnerIds = [];
  imageFile = null;
  document.getElementById('member-select-selected').innerHTML = '';
  document.getElementById('father-selected').innerHTML = '';
  document.getElementById('mother-selected').innerHTML = '';
  document.getElementById('partners-selected').innerHTML = '';
  document.getElementById('image-preview').style.display = 'none';
  document.getElementById('current-image').style.display = 'none';
  document.getElementById('current-image-path').textContent = '';
  document.getElementById("submitBtn").textContent = "Save Member";
  document.getElementById('selected-member-id').value = '';
  document.getElementById('img').value = '';
}

function selectMember(type, member) {
  const selectedContainer = document.getElementById(`${type}-selected`);
  
  if (type === 'father') {
    selectedFatherId = member.id;
    document.getElementById('fid-value').value = member.id;
    selectedContainer.innerHTML = '';
    addSelectedTag(selectedContainer, member, 'father');
  } 
  else if (type === 'mother') {
    selectedMotherId = member.id;
    document.getElementById('mid-value').value = member.id;
    selectedContainer.innerHTML = '';
    addSelectedTag(selectedContainer, member, 'mother');
  } 
  else if (type === 'partners') {
    if (!selectedPartnerIds.includes(member.id)) {
      selectedPartnerIds.push(member.id);
      document.getElementById('pids-value').value = selectedPartnerIds.join(',');
      addSelectedTag(selectedContainer, member, 'partners');
    }
  }
}

function addSelectedTag(container, member, type) {
  const tag = document.createElement('div');
  tag.classList.add('selected-tag');
  tag.innerHTML = `${member.name} <span data-id="${member.id}">×</span>`;
  
  // Add remove functionality
  tag.querySelector('span').addEventListener('click', (e) => {
    const id = parseInt(e.target.dataset.id);
    if (type === 'father') {
      selectedFatherId = null;
      document.getElementById('fid-value').value = '';
    } 
    else if (type === 'mother') {
      selectedMotherId = null;
      document.getElementById('mid-value').value = '';
    } 
    else if (type === 'partners') {
      selectedPartnerIds = selectedPartnerIds.filter(pid => pid !== id);
      document.getElementById('pids-value').value = selectedPartnerIds.join(',');
    }
    tag.remove();
  });
  
  container.appendChild(tag);
}

function populateDropdowns() {
  fetch('members.json')
    .then(res => res.ok ? res.json() : [])
    .then(data => {
      existingMembers = data;
      initializeSearchableDropdowns();
    });
}

document.getElementById("memberForm").addEventListener("submit", async function (e) {
  e.preventDefault();

  const getVal = id => document.getElementById(id)?.value.trim();
  
  // First, handle image upload if there's a new image file
  let imgPath = getVal("img");
  
  if (imageFile) {
    // Create FormData and append the file to upload
    const imageData = new FormData();
    imageData.append('image', imageFile);
    
    // If we already have a member ID (editing), use that for filename
    // Otherwise, we'll need to generate a new ID after saving the member
    if (editingMemberId) {
      imageData.append('id', editingMemberId);
    }
    
    try {
      // Upload the image first
      const uploadResponse = await fetch('upload_image.php', {
        method: 'POST',
        body: imageData
      });
      
      const uploadResult = await uploadResponse.json();
      
      if (uploadResult.success) {
        imgPath = uploadResult.path;
      } else {
        alert('Failed to upload image: ' + uploadResult.message);
        return;
      }
    } catch (error) {
      alert('Error uploading image: ' + error.message);
      return;
    }
  }
  
  const newMember = {
    id: editingMemberId || null, // send id for update
    name: getVal("name"),
    gender: getVal("gender"),
    relationship: getVal("relationship") || undefined,
    fid: selectedFatherId ? Number(selectedFatherId) : undefined,
    mid: selectedMotherId ? Number(selectedMotherId) : undefined,
    pids: selectedPartnerIds.length > 0 ? selectedPartnerIds : undefined,
    img: imgPath || undefined,
    bdate: getVal("bdate") ? Number(getVal("bdate")) : undefined,
    ddate: getVal("ddate") ? Number(getVal("ddate")) : undefined,
    tags: getVal("tags") ? getVal("tags").split(",").map(x => x.trim()) : undefined,
    orderId: getVal("orderId") ? Number(getVal("orderId")) : undefined
  };

  Object.keys(newMember).forEach(k => newMember[k] === undefined && delete newMember[k]);

  try {
    const saveResponse = await fetch("save_member.php", {
      method: "POST",
      body: JSON.stringify(newMember)
    });
    
    const saveResult = await saveResponse.json();
    
    if (saveResult.success) {
      // If this was a new member and we have an image file, now we need to rename it
      // Because now we have the generated ID
      if (!editingMemberId && imageFile && saveResult.data.id) {
        const updateImageData = new FormData();
        updateImageData.append('old_id', 'temp');
        updateImageData.append('new_id', saveResult.data.id);
        
        try {
          await fetch('rename_image.php', {
            method: 'POST',
            body: updateImageData
          });
        } catch (error) {
          console.error('Error renaming image:', error);
        }
      }
      
      document.getElementById("response").textContent = JSON.stringify(saveResult.data, null, 2);
      alert("Member saved successfully!");
      resetForm();
      populateDropdowns();
    } else {
      alert('Failed to save member: ' + (saveResult.message || 'Unknown error'));
    }
  } catch (error) {
    alert('Error saving member: ' + error.message);
  }
});

populateDropdowns();
</script>

</body>
</html>