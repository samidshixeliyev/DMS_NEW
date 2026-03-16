<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LegalActController;
use App\Http\Controllers\ActTypeController;
use App\Http\Controllers\IssuingAuthorityController;
use App\Http\Controllers\ExecutorController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\ExecutionNoteController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ExecutorDashboardController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\ReportController;

Route::get('login', [AuthController::class, 'showLogin'])->name('login');
Route::post('login', [AuthController::class, 'login'])->name('login.submit');
Route::post('logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {

    Route::get('/', function () {
        if (auth()->user()->user_role === 'executor') {
            return redirect()->route('executor.index');
        }
        return redirect()->route('legal-acts.index');
    });

    Route::middleware('role:executor,admin,manager')->group(function () {
        Route::get('executor/dashboard', [ExecutorDashboardController::class, 'index'])->name('executor.index');
        Route::post('executor/dashboard/load', [ExecutorDashboardController::class, 'load'])->name('executor.load');
        Route::get('executor/legal-acts/{legalAct}', [ExecutorDashboardController::class, 'show'])->name('executor.show');
        Route::post('executor/legal-acts/{legalAct}/status', [ExecutorDashboardController::class, 'storeStatus'])->name('executor.store-status');
        Route::get('executor/attachments/{attachment}/download', [ExecutorDashboardController::class, 'downloadAttachment'])->name('executor.download-attachment');
        Route::get('executor/attachments/{attachment}/preview', [ExecutorDashboardController::class, 'previewAttachment'])->name('executor.preview-attachment');
    });

    Route::middleware('role:admin,manager')->group(function () {
        Route::get('approvals', [ApprovalController::class, 'index'])->name('approvals.index');
        Route::post('approvals/load', [ApprovalController::class, 'load'])->name('approvals.load');
        Route::post('approvals/{statusLog}/approve', [ApprovalController::class, 'approve'])->name('approvals.approve');
        Route::post('approvals/{statusLog}/reject', [ApprovalController::class, 'reject'])->name('approvals.reject');
        Route::get('approvals/{legalAct}', [ApprovalController::class, 'show'])->name('approvals.show');

        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/load', [ReportController::class, 'load'])->name('reports.load');
        Route::get('reports/export-excel', [ReportController::class, 'exportExcel'])->name('reports.export-excel');
    });

    Route::post('legal-acts/load', [LegalActController::class, 'load'])->name('legal-acts.load');
    Route::get('legal-acts/export/excel', [LegalActController::class, 'exportExcel'])->name('legal-acts.export.excel');
    Route::get('legal-acts/export/word', [LegalActController::class, 'exportWord'])->name('legal-acts.export.word');
    Route::resource('legal-acts', LegalActController::class);

    Route::middleware('role:admin,manager')->group(function () {
        Route::post('legal-acts/{legalAct}/toggle-proof', [LegalActController::class, 'toggleProofRequired'])->name('legal-acts.toggle-proof');
    });

    Route::get('act-types', [ActTypeController::class, 'index'])->name('act-types.index');
    Route::get('act-types/{actType}', [ActTypeController::class, 'show'])->name('act-types.show');
    Route::get('act-types/{actType}/edit', [ActTypeController::class, 'edit'])->name('act-types.edit');
    Route::middleware('role:admin,manager')->group(function () {
        Route::post('act-types', [ActTypeController::class, 'store'])->name('act-types.store');
        Route::put('act-types/{actType}', [ActTypeController::class, 'update'])->name('act-types.update');
        Route::delete('act-types/{actType}', [ActTypeController::class, 'destroy'])->name('act-types.destroy');
    });

    Route::get('departments', [DepartmentController::class, 'index'])->name('departments.index');
    Route::get('departments/{department}', [DepartmentController::class, 'show'])->name('departments.show');
    Route::get('departments/{department}/edit', [DepartmentController::class, 'edit'])->name('departments.edit');
    Route::middleware('role:admin,manager')->group(function () {
        Route::post('departments', [DepartmentController::class, 'store'])->name('departments.store');
        Route::put('departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
        Route::delete('departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy');
    });

    Route::get('executors', [ExecutorController::class, 'index'])->name('executors.index');
    Route::get('executors/{executor}', [ExecutorController::class, 'show'])->name('executors.show');
    Route::get('executors/{executor}/edit', [ExecutorController::class, 'edit'])->name('executors.edit');
    Route::middleware('role:admin,manager')->group(function () {
        Route::post('executors', [ExecutorController::class, 'store'])->name('executors.store');
        Route::put('executors/{executor}', [ExecutorController::class, 'update'])->name('executors.update');
        Route::delete('executors/{executor}', [ExecutorController::class, 'destroy'])->name('executors.destroy');
    });

    Route::get('issuing-authorities', [IssuingAuthorityController::class, 'index'])->name('issuing-authorities.index');
    Route::get('issuing-authorities/{issuingAuthority}', [IssuingAuthorityController::class, 'show'])->name('issuing-authorities.show');
    Route::get('issuing-authorities/{issuingAuthority}/edit', [IssuingAuthorityController::class, 'edit'])->name('issuing-authorities.edit');
    Route::middleware('role:admin,manager')->group(function () {
        Route::post('issuing-authorities', [IssuingAuthorityController::class, 'store'])->name('issuing-authorities.store');
        Route::put('issuing-authorities/{issuingAuthority}', [IssuingAuthorityController::class, 'update'])->name('issuing-authorities.update');
        Route::delete('issuing-authorities/{issuingAuthority}', [IssuingAuthorityController::class, 'destroy'])->name('issuing-authorities.destroy');
    });

    Route::get('execution-notes', [ExecutionNoteController::class, 'index'])->name('execution-notes.index');
    Route::get('execution-notes/{executionNote}', [ExecutionNoteController::class, 'show'])->name('execution-notes.show');
    Route::get('execution-notes/{executionNote}/edit', [ExecutionNoteController::class, 'edit'])->name('execution-notes.edit');
    Route::middleware('role:admin,manager')->group(function () {
        Route::post('execution-notes', [ExecutionNoteController::class, 'store'])->name('execution-notes.store');
        Route::put('execution-notes/{executionNote}', [ExecutionNoteController::class, 'update'])->name('execution-notes.update');
        Route::delete('execution-notes/{executionNote}', [ExecutionNoteController::class, 'destroy'])->name('execution-notes.destroy');
    });

    Route::middleware('role:admin')->group(function () {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
        Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });
});