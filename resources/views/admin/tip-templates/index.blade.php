@extends('layouts.admin')

@section('title', 'Tip & Announcement Templates')
@section('page_title', 'Tip & Announcement Templates')

@section('content')
<div class="d-flex flex-column gap-4">

  <!-- TOP STATS & CREATE BUTTON -->
  <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
    <div class="d-flex flex-wrap gap-2">
      <span class="badge bg-light text-dark border px-3 py-2">
        <strong class="text-primary">{{ $totalCount }}</strong> Total Templates
      </span>
      <span class="badge bg-success-subtle text-success px-3 py-2">
        <strong>{{ $activeCount }}</strong> Active
      </span>
      <span class="badge bg-primary-subtle text-primary px-3 py-2">
        <strong>{{ $announcementCount }}</strong> Announcements
      </span>
      <span class="badge bg-info-subtle text-info px-3 py-2">
        <strong>{{ $savingTipCount }}</strong> Saving Tips
      </span>
    </div>

    <button type="button" class="btn btn-warning fw-semibold d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#createTemplateModal">
      <i class="bi bi-plus-lg"></i> Create Template / Announcement
    </button>
  </div>

  <!-- SEARCH & FILTERS -->
  <div class="admin-stat-card">
    <form action="{{ route('admin.tip-templates.index') }}" method="GET" class="row g-2 align-items-center">
      <div class="col-12 col-md-5">
        <div class="input-group">
          <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
          <input type="text" name="search" class="form-control border-start-0" placeholder="Search templates by title or content..." value="{{ $search }}">
        </div>
      </div>
      <div class="col-6 col-md-3">
        <select name="type" class="form-select" onchange="this.form.submit()">
          <option value="all" {{ $type === 'all' ? 'selected' : '' }}>All Types</option>
          <option value="announcement" {{ $type === 'announcement' ? 'selected' : '' }}>Announcements</option>
          <option value="saving_tip" {{ $type === 'saving_tip' ? 'selected' : '' }}>Saving Tips</option>
          <option value="general_tip" {{ $type === 'general_tip' ? 'selected' : '' }}>General Tips</option>
        </select>
      </div>
      <div class="col-6 col-md-2">
        <select name="status" class="form-select" onchange="this.form.submit()">
          <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All Status</option>
          <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active Only</option>
          <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Inactive Only</option>
        </select>
      </div>
      <div class="col-12 col-md-2 d-flex gap-2">
        <button type="submit" class="btn btn-warning flex-fill fw-semibold">Filter</button>
        @if (!empty($search) || $type !== 'all' || $status !== 'all')
          <a href="{{ route('admin.tip-templates.index') }}" class="btn btn-outline-secondary" title="Reset Filters"><i class="bi bi-x-lg"></i></a>
        @endif
      </div>
    </form>
  </div>

  <!-- TEMPLATES TABLE CARD -->
  <div class="admin-stat-card p-0 overflow-hidden">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="padding-left:1.25rem;">Title &amp; Message</th>
            <th>Type</th>
            <th>Status</th>
            <th>Created By</th>
            <th>Date</th>
            <th class="text-end" style="padding-right:1.25rem;">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($templates as $tpl)
            <tr>
              <td style="padding-left:1.25rem; max-width:350px;">
                <div class="d-flex align-items-start gap-2">
                  <div class="mt-1"><i class="bi {{ $tpl->type_icon }} text-warning"></i></div>
                  <div style="min-width:0;">
                    <div class="fw-bold text-dark text-truncate">{{ $tpl->title }}</div>
                    <div class="text-muted small text-truncate" style="font-size:0.75rem;">{{ $tpl->message }}</div>
                  </div>
                </div>
              </td>
              <td>
                <span class="badge {{ $tpl->type_badge_class }}">
                  {{ ucfirst(str_replace('_', ' ', $tpl->type)) }}
                </span>
              </td>
              <td>
                @if ($tpl->status === 'active')
                  <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle-fill"></i> Active</span>
                @else
                  <span class="badge bg-secondary-subtle text-secondary"><i class="bi bi-slash-circle"></i> Inactive</span>
                @endif
              </td>
              <td class="small text-secondary">
                {{ $tpl->creator->name ?? 'Administrator' }}
              </td>
              <td class="text-muted small">
                {{ $tpl->created_at ? $tpl->created_at->format('M d, Y') : 'N/A' }}
              </td>
              <td class="text-end" style="padding-right:1.25rem;">
                <div class="d-inline-flex align-items-center gap-1">
                  <!-- Edit Button -->
                  <button type="button" class="btn btn-sm btn-outline-secondary" 
                          data-bs-toggle="modal" 
                          data-bs-target="#editTemplateModal{{ $tpl->id }}" 
                          title="Edit Template">
                    <i class="bi bi-pencil"></i>
                  </button>

                  <!-- Status Toggle -->
                  <form action="{{ route('admin.tip-templates.status', $tpl->id) }}" method="POST" style="display:inline;">
                    @csrf
                    @method('PATCH')
                    @if ($tpl->status === 'active')
                      <button type="submit" class="btn btn-sm btn-outline-warning" title="Deactivate">
                        <i class="bi bi-toggle-on"></i>
                      </button>
                    @else
                      <button type="submit" class="btn btn-sm btn-outline-success" title="Activate">
                        <i class="bi bi-toggle-off"></i>
                      </button>
                    @endif
                  </form>

                  <!-- Delete Button -->
                  <form action="{{ route('admin.tip-templates.destroy', $tpl->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete template {{ $tpl->title }}?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete template">
                      <i class="bi bi-trash"></i>
                    </button>
                  </form>
                </div>

                <!-- EDIT MODAL FOR THIS TEMPLATE -->
                <div class="modal fade text-start" id="editTemplateModal{{ $tpl->id }}" tabindex="-1" aria-labelledby="editTplLabel{{ $tpl->id }}" aria-hidden="true">
                  <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                      <form action="{{ route('admin.tip-templates.update', $tpl->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                          <h5 class="modal-title" id="editTplLabel{{ $tpl->id }}"><i class="bi bi-pencil-square text-warning me-1"></i> Edit Template / Announcement</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body d-flex flex-column gap-3">
                          <div>
                            <label class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" value="{{ $tpl->title }}" required maxlength="150">
                          </div>
                          <div class="row g-2">
                            <div class="col-12 col-md-6">
                              <label class="form-label fw-semibold">Type <span class="text-danger">*</span></label>
                              <select name="type" class="form-select" required>
                                <option value="announcement" {{ $tpl->type === 'announcement' ? 'selected' : '' }}>Announcement</option>
                                <option value="saving_tip" {{ $tpl->type === 'saving_tip' ? 'selected' : '' }}>Saving Tip</option>
                                <option value="general_tip" {{ $tpl->type === 'general_tip' ? 'selected' : '' }}>General Financial Tip</option>
                              </select>
                            </div>
                            <div class="col-12 col-md-6">
                              <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                              <select name="status" class="form-select" required>
                                <option value="active" {{ $tpl->status === 'active' ? 'selected' : '' }}>Active (Visible to Students)</option>
                                <option value="inactive" {{ $tpl->status === 'inactive' ? 'selected' : '' }}>Inactive (Draft / Hidden)</option>
                              </select>
                            </div>
                          </div>
                          <div>
                            <label class="form-label fw-semibold">Message Content <span class="text-danger">*</span></label>
                            <textarea name="message" class="form-control" rows="4" required maxlength="2000">{{ $tpl->message }}</textarea>
                          </div>
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                          <button type="submit" class="btn btn-warning fw-semibold">Save Template</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>

              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center py-5 text-muted">
                <i class="bi bi-megaphone d-block mb-2" style="font-size:2rem;"></i>
                No templates or announcements found.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <!-- PAGINATION -->
    @if ($templates->hasPages())
      <div class="p-3 border-top d-flex justify-content-between align-items-center">
        <div class="small text-muted">
          Showing {{ $templates->firstItem() }} to {{ $templates->lastItem() }} of {{ $templates->total() }} templates
        </div>
        <div>
          {{ $templates->links() }}
        </div>
      </div>
    @endif
  </div>

</div>

<!-- CREATE TEMPLATE MODAL -->
<div class="modal fade" id="createTemplateModal" tabindex="-1" aria-labelledby="createTemplateModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form action="{{ route('admin.tip-templates.store') }}" method="POST">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title" id="createTemplateModalLabel"><i class="bi bi-plus-circle-fill text-warning me-1"></i> New System Announcement / Tip Template</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body d-flex flex-column gap-3">
          <div>
            <label class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
            <input type="text" name="title" class="form-control" placeholder="e.g. End of Semester Budgeting Guide" required maxlength="150">
          </div>
          <div class="row g-2">
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold">Template Type <span class="text-danger">*</span></label>
              <select name="type" class="form-select" required>
                <option value="announcement" selected>System Announcement (Dashboard Banner)</option>
                <option value="saving_tip">Personalized Saving Tip Template</option>
                <option value="general_tip">General Financial Advice</option>
              </select>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold">Initial Status <span class="text-danger">*</span></label>
              <select name="status" class="form-select" required>
                <option value="active" selected>Active (Published)</option>
                <option value="inactive">Inactive (Draft)</option>
              </select>
            </div>
          </div>
          <div>
            <label class="form-label fw-semibold">Message Content <span class="text-danger">*</span></label>
            <textarea name="message" class="form-control" rows="4" placeholder="Write the announcement or tips body for student users..." required maxlength="2000"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning fw-semibold">Create &amp; Publish</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
