# API Routes – Quick Reference (Frontend)

> **Purpose:**  
> This is a lightweight reference for frontend development only.  
> All routes return **JSON**, not HTML.

---

## General Notes

- All responses use `application/json`
- Standard response shape:

```json
{
  "success": true,
  "data": {}
}
```

or

```json
{
  "success": false,
  "error": "Error message"
}
```

- Protected routes require a valid session cookie  
  Use: `fetch(url, { credentials: "include" })`

---

## Public Routes (No Login Required)

### 1. Health Check

**GET** `/health`

**Purpose:** API availability check

**Request:**  
No parameters

**Response (example):**
```json
{
  "success": true,
  "data": { "status": "ok" }
}
```

---

### 2. Login

**POST** `/auth/login`

**Purpose:** Authenticate user and start session

**Headers:**
```
Content-Type: application/json
```

**Body:**
```json
{
  "email": "admin@example.com",
  "password": "password"
}
```

**Response (example):**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "Admin User",
      "role": "admin"
    }
  }
}
```

---

### 3. Logout

**POST** `/auth/logout`

**Purpose:** Destroy session

**Response:**
```json
{
  "success": true
}
```

---

### 4. Current User

**GET** `/auth/me`

**Purpose:** Get logged-in user details

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "role": "admin"
  }
}
```

---

## Protected Routes  
(All require `Auth::requireLogin()`)

---

## Grades (Admin Only)

### 5. Get All Grades

**GET** `/grades`

**Auth:** `admin`

**Query Params:**  
None

**Response:**
```json
{
  "success": true,
  "data": [
    { "id": 1, "name": "Grade 1" },
    { "id": 2, "name": "Grade 2" }
  ]
}
```

---

## Classes (Admin Only)

### 6. Get Classes by Grade

**GET** `/get-classes`

**Auth:** `admin`

**Query Params:**
| Name | Type | Required |
|----|----|----|
| grade_id | int | Yes |

**Example:**
```
/get-classes?grade_id=1
```

**Response:**
```json
{
  "success": true,
  "data": [
    { "id": 10, "grade_id": 1, "name": "Grade 1A" },
    { "id": 11, "grade_id": 1, "name": "Grade 1B" }
  ]
}
```

---

## Student Reports

> If `year` is omitted, backend defaults to the most recent year.

### 7. Admin – Student Report

**GET** `/student-report`

**Auth:** `admin`

**Query Params:**
| Name | Type | Required |
|----|----|----|
| student_id | int | Yes |
| year | int | No |

**Response (high level):**
```json
{
  "success": true,
  "data": {
    "student": { "id": 5, "name": "John Doe" },
    "year": 2025,
    "subjects": [
      { "subject": "Math", "term1": 75, "term2": 80, "term3": null, "average": 78 }
    ]
  }
}
```

---

### 8. Teacher – Student Report

**GET** `/teacher/student-report`

**Auth:** `teacher`

**Query Params:** same as admin version

**Response:**  
Same structure as admin, but may be restricted.

---

## Class Reports

> If `year` is omitted, backend defaults to most recent year.

### 9. Admin – Class Report

**GET** `/class-report`

**Auth:** `admin`

**Query Params:**
| Name | Type | Required |
|----|----|----|
| class_id | int | Yes |
| year | int | No |

**Response (high level):**
```json
{
  "success": true,
  "data": {
    "class": { "id": 3, "name": "Grade 3A" },
    "students": [
      {
        "student": "Jane Doe",
        "subjects": []
      }
    ]
  }
}
```

---

### 10. Teacher – Class Report

**GET** `/teacher/class-report`

**Auth:** `teacher`

**Query Params:**
| Name | Type | Required |
|----|----|----|
| year | int | No |

**Notes:**
- `class_id` is derived from the teacher session

---

## Common Frontend Fetch Examples

```js
fetch("/grades", {
  credentials: "include"
});
```

```js
fetch(`/get-classes?grade_id=${gradeId}`, {
  credentials: "include"
});
```

```js
fetch("/auth/login", {
  method: "POST",
  credentials: "include",
  headers: { "Content-Type": "application/json" },
  body: JSON.stringify({ email, password })
});
```

---

## Expected HTTP Status Codes

| Code | Meaning |
|----|----|
| 200 | OK |
| 400 | Bad request (missing/invalid params) |
| 401 | Not authenticated |
| 403 | Forbidden (wrong role) |
| 404 | Route not found |
