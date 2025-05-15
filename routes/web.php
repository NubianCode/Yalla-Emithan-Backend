<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;


$router->group(['prefix' => 'auth'], function () use ($router) {

    //Auth APIs
    $router->post('accountantLogin', 'AuthController@accountantLogin');
    $router->post('visitor', 'AuthController@visitor');
    $router->post('setupProfile', 'AuthController@setupProfile');
    $router->post('setupProfileV2', 'AuthController@setupProfileV2');
    $router->post('login', 'AuthController@login');
    $router->post('loginV2', 'AuthController@loginV2');
    $router->post('loginV3', 'AuthController@loginV3');
    $router->post('logout', 'AuthController@logout');
    $router->post('deleteAccount', 'AuthController@deleteAccount');
    $router->post('refresh', 'AuthController@refresh');
    $router->post('me', 'AuthController@me');
    $router->post('sendCodeToUser', 'AuthController@sendCodeToUser');
    $router->post('sendCodeToVisitor', 'AuthController@sendCodeToVisitor');
    $router->post('register', 'AuthController@register');
    $router->post('resetPassword', 'AuthController@resetPassword');
    $router->post('checkCode', 'AuthController@checkCode');
    $router->post('sendCode','AuthController@sendCode');
    $router->post('notePayment', 'AuthController@notePayment');
    $router->post('classPayment', 'AuthController@classPayment');
    $router->post('addMBookRequest', 'AuthController@addMBookRequest');
    $router->post('test', 'AuthController@test');
    $router->post('sendMessageToTopic', 'AuthController@sendMessageToTopic');
    $router->post('sendMessageToSupervisor', 'AuthController@sendMessageToSupervisor');
    $router->post('sendMessageToDriver', 'AuthController@sendMessageToDriver');
    $router->post('sendLocation', 'AuthController@sendLocation');
    $router->post('activeMBookRequest', 'AuthController@activeMBookRequest');
    $router->post('disactiveMBookRequest', 'AuthController@disactiveMBookRequest');
    $router->post('whatsappMessage', 'AuthController@whatsappMessage');
    $router->post('editName', 'AuthController@editName');
    $router->post('editProfileImage', 'AuthController@editProfileImage');


    //Dashbord
    $router->post('supervisorLogin', 'AuthController@supervisorLogin');
});

$router->group(['prefix' => 'students'], function () use ($router) {
    $router->post('changeName', 'StudentController@changeName');
    $router->post('addComplaint', 'StudentController@addComplaint');
    $router->post('selectAvatar', 'StudentController@selectAvatar');
    $router->post('getLevels', 'StudentController@getLevels');
    $router->post('getLevelsV2', 'StudentController@getLevelsV2');
    $router->get('getLevelsV3', 'StudentController@getLevelsV2');
    $router->get('getPayments', 'StudentController@getPayments');
    $router->get('getReports', 'StudentController@getReports');
    $router->post('subscribe', 'StudentController@subscribe');
    $router->post('updateLobby', 'StudentController@updateLobby');
    $router->get('getIncome','StudentController@getIncome');
    $router->get('getWithdrawals','StudentController@getWithdrawals');
    $router->post('updateStudentClass', 'StudentController@updateStudentClass');
    $router->post('addResult', 'StudentController@addResult');
    $router->post('addResultV2', 'StudentController@addResultV2');
    //Dashbord
    $router->get('getStudents', 'StudentController@getStudents');
    $router->get('getTop10', 'StudentController@getTop10');
    $router->get('getTop10V2', 'StudentController@getTop10V2');
    $router->patch('changeStudentStatus', 'StudentController@changeStudentStatus');
});

$router->group(['prefix' => 'exams'], function () use ($router) {
    $router->post('getExam', 'ExamController@getExam');
        $router->post('getExamV2', 'ExamController@getExamV2');
    $router->post('getTest', 'ExamController@getTest');
    $router->post('addResult', 'ExamController@addResult');
    $router->post('addQuestionComplaint', 'ExamController@addQuestionComplaint');
    $router->post('getMatchQuestions', 'ExamController@getMatchQuestions');

});


$router->group(['prefix' => 'schools'], function () use ($router) {
    
    $router->get('getSchools', 'SchoolController@getSchools');
    $router->get('getSchoolsV2', 'SchoolController@getSchoolsV2');
    $router->get('getStudentsByKey', 'SchoolController@getStudentsByKey');
    $router->get('getCurriculumProgress', 'SchoolController@getCurriculumProgress');
    $router->get('getStudentHomeworks', 'HomeworksController@getStudentHomeworks');
    $router->get('getExmas', 'SchoolController@getExmas');
    $router->get('getAppointments', 'SchoolController@getAppointments');
    $router->post('updateNationalNumber', 'SchoolController@updateNationalNumber');
    $router->post('addSchoolBranchRoomScheduleSubject', 'SchoolController@addSchoolBranchRoomScheduleSubject');
    $router->post('updateSolution', 'SchoolController@updateSolution');
    $router->get('getNotifications', 'SchoolController@getNotifications');
    $router->get('getSchoolNotifications', 'SchoolController@getSchoolNotifications');
    $router->get('getStudents', 'SchoolController@getStudents');
    $router->get('getStudentDataByNationalId', 'SchoolController@getStudentDataByNationalId');
    $router->get('getSchedules', 'SchoolController@getSchedules');
    $router->get('getHomeworks', 'HomeworksController@getHomeworks');
    $router->post('deleteSchoolBranchRoomScheduleSubject', 'SchoolController@deleteSchoolBranchRoomScheduleSubject');
    $router->post('editSchoolBranchRoomScheduleSubject', 'SchoolController@editSchoolBranchRoomScheduleSubject');
    
    $router->post('addSchedule', 'SchoolController@addSchedule');
    $router->post('addSchoolInstallment', 'SchoolController@addSchoolInstallment');
    $router->post('editSchoolInstallment', 'SchoolController@editSchoolInstallment');
    $router->post('deleteSchoolInstallment', 'SchoolController@deleteSchoolInstallment');
    
    $router->get('getSchoolCosts', 'SchoolController@getSchoolCosts');
    $router->post('addSchoolCost','SchoolController@addSchoolCost');
    $router->post('editSchoolCost','SchoolController@editSchoolCost');
    $router->post('addHomework','HomeworksController@addHomework');
    $router->post('editHomework','HomeworksController@editHomework');
    $router->get('getAttendances', 'SchoolController@getAttendances');
    $router->patch('editAttendance', 'SchoolController@editAttendance');
    $router->get('getRegistrations', 'SchoolController@getRegistrations');
    $router->post('editRegistration', 'SchoolController@editRegistration');
    
    $router->post('registerNewStudent', 'SchoolController@registerNewStudent');
    $router->post('registerNewStudentViaPlatform', 'SchoolController@registerNewStudentViaPlatform');
    $router->post('registerExistingStudent', 'SchoolController@registerExistingStudent');
    $router->post('registerExistingStudentViaPlatform', 'SchoolController@registerExistingStudentViaPlatform');
    $router->post('editRegistrationViaPlatform', 'SchoolController@editRegistrationViaPlatform');
    $router->post('updateStudentInfo', 'SchoolController@updateStudentInfo');
    $router->get('getSubjects', 'SchoolController@getSubjects');
    
    
    $router->get('getSupervisors', 'SchoolController@getSupervisors');
    
    $router->get('getBranches','BranchController@getBranches');
    $router->post('addRoomAPI','BranchController@addRoomAPI');
    $router->post('editRoomAPI','BranchController@editRoomAPI');
    $router->post('deleteRoomAPI','BranchController@deleteRoomAPI');
    $router->post('changeStudentRoom','BranchController@changeStudentRoom');
    $router->post('addBranch','BranchController@addBranch');
    $router->post('editBranch','BranchController@editBranch');
    
    $router->post('addSupervisor','SchoolEmployeeController@addSupervisor');
    $router->post('editSupervisor','SchoolEmployeeController@editSupervisor');
    
    $router->post('addTeacher','SchoolEmployeeController@addTeacher');
    $router->post('editTeacher','SchoolEmployeeController@editTeacher');
    $router->get('getTeachers','SchoolEmployeeController@getTeachers');
    
    
    $router->post('markSolution','HomeworksController@markSolution');
    
    
    //school videos
    $router->get('getSchoolVideos','SchoolVideoController@getSchoolVideos');
    $router->post('sendNotification','SchoolController@sendNotification');
    $router->post('editNotification','SchoolController@editNotification');
    $router->post('watchSchoolVideo','SchoolVideoController@watchSchoolVideo');
    $router->post('deleteNotification','SchoolController@deleteNotification');
    $router->post('addOrEditSchoolSubjectTotalVideo','SchoolController@addOrEditSchoolSubjectTotalVideo');
    $router->get('getSchoolVideosChapters','SchoolController@getSchoolVideosChapters');
    $router->post('addSchoolVideoChapter','SchoolController@addSchoolVideoChapter');
    $router->post('editSchoolVideoChapter','SchoolController@editSchoolVideoChapter');
    $router->get('getSchoolVideos','SchoolController@getSchoolVideos');
    $router->post('reorderVideos','SchoolController@reorderVideos');
    $router->post('deleteSchoolVideo','SchoolController@deleteSchoolVideo');
    $router->post('addSchoolVideo','SchoolController@addSchoolVideo');

    
    
    
    
});


//Dashboard*************************************
$router->group(['prefix' => 'supervisors'], function () use ($router) {
    $router->get('getSupervisors', 'SupervisorController@getSupervisors');
    $router->get('getPayments', 'SupervisorController@getPayments');
    $router->get('getReports', 'SupervisorController@getReports');
    $router->get('getComplaints', 'SupervisorController@getComplaints');
    $router->post('addSupervisor', 'SupervisorController@addSupervisor');
    $router->post('addSubscription', 'SupervisorController@addSubscription');
    $router->patch('editSupervisor', 'SupervisorController@editSupervisor');
    $router->patch('changeSupervisorStatus', 'SupervisorController@changeSupervisorStatus');
});

$router->group(['prefix' => 'levels'], function () use ($router) {
    $router->post('addLevel', 'LevelController@addLevel');
    $router->get('getLevels', 'LevelController@getLevels');
    $router->patch('editLevel', 'LevelController@editLevel');
});

$router->group(['prefix' => 'classes'], function () use ($router) {
    $router->post('addClass', 'ClassController@addClass');
    $router->get('getClasses', 'ClassController@getClasses');
    $router->get('getAllClasses', 'ClassController@getAllClasses');
    $router->patch('editClass', 'ClassController@editClass');
    $router->get('getClassesQuestions', 'ClassController@getClassesQuestions');
});

$router->group(['prefix' => 'chapters'], function () use ($router) {
    $router->post('addChapter', 'ChapterController@addChapter');
    $router->get('getChapters', 'ChapterController@getChapters');
    $router->patch('editChapter', 'ChapterController@editChapter');
     $router->post('deleteChapter', 'ChapterController@deleteChapter');
    
});

$router->group(['prefix' => 'lessons'], function () use ($router) {
    $router->post('addLesson', 'LessonController@addLesson');
    $router->get('getLessons', 'LessonController@getLessons');
    $router->patch('editLesson', 'LessonController@editLesson');
    $router->post('deleteLesson', 'LessonController@deleteLesson');
    $router->post('emptyLesson', 'LessonController@emptyLesson');
    
});

$router->group(['prefix' => 'questions'], function () use ($router) {
    $router->post('editQuestionBody', 'QuestionController@editQuestionBody');
    $router->post('editRightAnswer', 'QuestionController@editRightAnswer');
    $router->post('editAnswers', 'QuestionController@editAnswers');
    $router->patch('deleteQuestion', 'QuestionController@deleteQuestion');
    $router->post('addQuestion', 'QuestionController@addQuestion');
    $router->get('getQuestions', 'QuestionController@getQuestion');
    $router->get('print', 'QuestionController@print');
    $router->patch('editQuestion', 'QuestionController@editQuestion');
    $router->get('getQuestionsComplaints', 'QuestionController@getQuestionsComplaints');
    
});

$router->group(['prefix' => 'subjects'], function () use ($router) {
    $router->post('addSubject', 'SubjectController@addSubject');
    $router->post('addBook', 'SubjectController@addBook');
    $router->post('addExam', 'SubjectController@addExam');
    $router->post('deleteExam', 'SubjectController@deleteExam');
    $router->post('editExam', 'SubjectController@editExam');
    $router->get('getSubjects', 'SubjectController@getSubjects');
    $router->get('getExams', 'SubjectController@getExams');
    $router->patch('editSubject', 'SubjectController@editSubject');
    
    
});

$router->group(['prefix' => 'liveClasses'], function () use ($router) {
    $router->get('getLiveClasses', 'LiveClassesController@getLiveClasses');
    $router->get('getLiveClassesV2', 'LiveClassesController@getLiveClassesV2');
    $router->post('createLiveClass', 'LiveClassesController@createLiveClass');
    $router->post('endLiveClass', 'LiveClassesController@endLiveClass');
    $router->post('getLiveClassByUuid', 'LiveClassesController@getLiveClassByUuid');

});

$router->group(['prefix' => 'posts'], function () use ($router) {
    $router->post('addPost', 'PostController@addPost');
    $router->get('getPosts', 'PostController@getPosts');
    $router->get('getComments', 'PostController@getComments');
    $router->post('upPost', 'PostController@upPost');
    $router->post('comment', 'PostController@comment');
    $router->post('loveComment', 'PostController@loveComment');
    $router->post('reportPost', 'PostController@reportPost');
    $router->get('getNotifications', 'PostController@getNotifications');
    $router->post('deletePost', 'PostController@deletePost');
    $router->post('cancelFriendship', 'PostController@cancelFriendship');
    $router->post('block', 'PostController@block');
    $router->get('getPostsByStudentId', 'PostController@getPostsByStudentId');
    $router->post('sendFriendRequest', 'PostController@sendFriendRequest');
    $router->post('blockStudent', 'PostController@blockStudent');
    $router->get('getBlockedStudents', 'PostController@getBlockedStudents');
    $router->post('unblockStudent', 'PostController@unblockStudent');
    $router->post('acceptFriendRequest', 'PostController@acceptFriendRequest');
    $router->post('removeFriendRequest', 'PostController@removeFriendRequest');
    $router->post('cancelFriendship', 'PostController@cancelFriendship');
});

$router->group(['prefix' => 'videos'], function () use ($router) {
    $router->get('getVideos', 'VideoController@getVideos');
    $router->post('uploadVideo', 'VideoController@uploadVideo');
    $router->post('editVideo', 'VideoController@editVideo');
    $router->post('watchVideo' , 'VideoController@watchVideo');
    $router->post('updateVideoIndex' , 'VideoController@updateVideoIndex');
    $router->post('deleteVideo' , 'VideoController@deleteVideo');
    $router->get('video/{id}' , 'VideoController@streamVideo');
});

$router->group(['prefix' => 'messages'], function () use ($router) {
    $router->get('getFriends', 'MessageController@getFriends');
    $router->get('getMessages', 'MessageController@getMessages');
    $router->get('getStudents', 'MessageController@getStudents');
    $router->post('sendMessage', 'MessageController@sendMessage');
    $router->post('updateStatus', 'MessageController@updateStatus');
    $router->post('getConversationByFriendId', 'MessageController@getConversationByFriendId');
    $router->post('seen', 'MessageController@seen');
});

$router->get('/', function () use ($router) {
    return "mas";
});
