<?php

namespace App\Http\Controllers\Students;

use App\Entities\Users\User;
use Illuminate\Http\Request;
use App\Entities\Students\Student;
use Illuminate\Support\Facades\DB;
use App\Entities\Classes\ClassName;
use App\Http\Controllers\Controller;

class StudentController extends Controller
{
    /**
     * Display a listing of the student.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $studentQuery = Student::query();
        $studentQuery->where('name', 'like', '%'.request('q').'%');
        $students = $studentQuery->paginate(25);

        return view('students.index', compact('students'));
    }

    /**
     * Show the form for creating a new student.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $this->authorize('create', new Student);

        $classes = ClassName::orderBy('level_id')->orderBy('name')->get();

        return view('students.create', compact('classes'));
    }

    /**
     * Store a newly created student in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Routing\Redirector
     */
    public function store(Request $request)
    {
        $this->authorize('create', new Student);

        $newStudent = $request->validate([
            'class_id'      => 'required|exists:class_names,id',
            'nis'           => 'required|max:60|unique:students,nis',
            'nisn'          => 'nullable|max:60|unique:students,nis,nisn',
            'name'          => 'required|max:60',
            'pob'           => 'required|max:60',
            'dob'           => 'required|date|date_format:Y-m-d',
            'gender_id'     => 'required|numeric|in:0,1',
            'religion_id'   => 'required|numeric|in:1,2,3,4,5,6,99',
            'phone'         => 'nullable|max:14',
            'email'         => 'nullable|max:60',
            'address'       => 'nullable|max:255',
            'father_name'   => 'nullable|max:60',
            'father_phone'  => 'nullable|max:14',
            'mother_name'   => 'nullable|max:60',
            'mother_phone'  => 'nullable|max:14',
            'wali_name'     => 'nullable|max:60',
            'wali_relation' => 'nullable|max:60',
            'wali_phone'    => 'nullable|max:14',
        ]);
        $newStudent['is_active'] = 1;
        $newStudent['creator_id'] = auth()->id();

        DB::beginTransaction();

        $user = User::create([
            'name'      => strtoupper($newStudent['name']),
            'username'  => $newStudent['nis'],
            'email'     => $newStudent['email'],
            'role_id'   => 3,
            'is_active' => 1,
            'password'  => bcrypt(str_replace('-', '', $newStudent['dob'])),
        ]);

        $newStudent['login_id'] = $user->id;

        $student = Student::create($newStudent);

        DB::commit();

        flash(__('student.created'), 'success');

        return redirect()->route('students.show', $student);
    }

    /**
     * Display the specified student.
     *
     * @param  \App\Student  $student
     * @return \Illuminate\View\View
     */
    public function show(Student $student)
    {
        return view('students.show', compact('student'));
    }

    /**
     * Show the form for editing the specified student.
     *
     * @param  \App\Student  $student
     * @return \Illuminate\View\View
     */
    public function edit(Student $student)
    {
        $this->authorize('update', $student);

        $classes = ClassName::orderBy('level_id')->orderBy('name')->get();

        return view('students.edit', compact('student', 'classes'));
    }

    /**
     * Update the specified student in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Entities\Students\Student  $student
     * @return \Illuminate\Routing\Redirector
     */
    public function update(Request $request, Student $student)
    {
        $this->authorize('update', $student);

        $studentData = $request->validate([
            'class_id'      => 'required|exists:class_names,id',
            'nis'           => 'required|max:60|unique:students,nis,'.$student->id,
            'nisn'          => 'nullable|max:60|unique:students,nisn,'.$student->id,
            'name'          => 'required|max:60',
            'pob'           => 'required|max:60',
            'dob'           => 'required|date|date_format:Y-m-d',
            'gender_id'     => 'required|numeric|in:0,1',
            'religion_id'   => 'required|numeric|in:1,2,3,4,5,6,99',
            'phone'         => 'nullable|max:14',
            'email'         => 'nullable|max:60',
            'address'       => 'nullable|max:255',
            'father_name'   => 'nullable|max:60',
            'father_phone'  => 'nullable|max:14',
            'mother_name'   => 'nullable|max:60',
            'mother_phone'  => 'nullable|max:14',
            'wali_name'     => 'nullable|max:60',
            'wali_relation' => 'nullable|max:60',
            'wali_phone'    => 'nullable|max:14',
        ]);

        DB::beginTransaction();

        $student->login->update([
            'name'     => strtoupper($studentData['name']),
            'username' => $studentData['nis'],
            'email'    => $studentData['email'],
            'password' => bcrypt(str_replace('-', '', $studentData['dob'])),
        ]);

        $student->update($studentData);

        DB::commit();

        flash(__('student.updated'), 'information');

        return redirect()->route('students.show', $student);
    }

    /**
     * Remove the specified student from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Entities\Students\Student  $student
     * @return \Illuminate\Routing\Redirector
     */
    public function destroy(Request $request, Student $student)
    {
        $this->authorize('delete', $student);

        $request->validate(['student_id' => 'required']);

        if ($request->get('student_id') == $student->id) {

            DB::beginTransaction();

            $student->delete();
            $student->login->delete();

            DB::commit();

            flash(__('student.deleted'), 'error');

            return redirect()->route('students.index');
        }

        return back();
    }
}
