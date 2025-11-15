import React, { useState, useEffect } from 'react';
import { projects, counters, directAccounts, goals, sync, report } from '../../api';
import './Projects.css';

export default function Projects() {
  const [projectList, setProjectList] = useState([]);
  const [loading, setLoading] = useState(false);
  const [newProject, setNewProject] = useState('');
  const [expandedId, setExpandedId] = useState(null);
  const [syncStatus, setSyncStatus] = useState({});
  const [reportData, setReportData] = useState({});

  useEffect(() => {
    loadProjects();
  }, []);

  const loadProjects = async () => {
    setLoading(true);
    try {
      const result = await projects.list();
      if (result.success) {
        setProjectList(result.data);
      }
    } catch (error) {
      console.error('Failed to load projects', error);
    }
    setLoading(false);
  };

  const handleCreateProject = async () => {
    if (!newProject.trim()) return;

    try {
      const result = await projects.create({ name: newProject });
      if (result.success) {
        setProjectList([...projectList, result.data]);
        setNewProject('');
      }
    } catch (error) {
      console.error('Failed to create project', error);
    }
  };

  const handleDeleteProject = async (id) => {
    try {
      const result = await projects.delete(id);
      if (result.success) {
        setProjectList(projectList.filter(p => p.id !== id));
      }
    } catch (error) {
      console.error('Failed to delete project', error);
    }
  };

  const handleTriggerSync = async (projectId) => {
    try {
      const result = await sync.trigger(projectId);
      if (result.success) {
        setSyncStatus(prev => ({ ...prev, [projectId]: 'syncing' }));
        // Check status periodically
        setTimeout(() => checkSyncStatus(projectId), 2000);
      }
    } catch (error) {
      console.error('Failed to trigger sync', error);
    }
  };

  const checkSyncStatus = async (projectId) => {
    try {
      const result = await sync.status(projectId);
      if (result.success) {
        setSyncStatus(prev => ({ ...prev, [projectId]: result.data.status }));
      }
    } catch (error) {
      console.error('Failed to check sync status', error);
    }
  };

  const handleLoadReport = async (projectId) => {
    try {
      const result = await report.get(projectId);
      if (result.success) {
        setReportData(prev => ({ ...prev, [projectId]: result.data }));
      }
    } catch (error) {
      console.error('Failed to load report', error);
    }
  };

  const toggleExpanded = (id) => {
    setExpandedId(expandedId === id ? null : id);
  };

  return (
    <div className="projects-container">
      <h1>Projects</h1>

      <div className="create-project-form">
        <input
          type="text"
          placeholder="Project name"
          value={newProject}
          onChange={(e) => setNewProject(e.target.value)}
          onKeyPress={(e) => e.key === 'Enter' && handleCreateProject()}
        />
        <button onClick={handleCreateProject}>Create Project</button>
      </div>

      {loading ? (
        <p>Loading...</p>
      ) : projectList.length === 0 ? (
        <p>No projects yet</p>
      ) : (
        <div className="projects-list">
          {projectList.map((project) => (
            <div key={project.id} className="project-card">
              <div className="project-header" onClick={() => toggleExpanded(project.id)}>
                <h2>{project.name}</h2>
                <div className="project-actions">
                  <button
                    className="btn-sync"
                    onClick={(e) => {
                      e.stopPropagation();
                      handleTriggerSync(project.id);
                    }}
                  >
                    {syncStatus[project.id] === 'syncing' ? 'Syncing...' : 'Sync'}
                  </button>
                  <button
                    className="btn-report"
                    onClick={(e) => {
                      e.stopPropagation();
                      handleLoadReport(project.id);
                    }}
                  >
                    Report
                  </button>
                  <button
                    className="btn-delete"
                    onClick={(e) => {
                      e.stopPropagation();
                      handleDeleteProject(project.id);
                    }}
                  >
                    Delete
                  </button>
                </div>
              </div>

              {expandedId === project.id && (
                <div className="project-details">
                  <div className="section">
                    <h3>Counters</h3>
                    <ProjectCounters projectId={project.id} />
                  </div>

                  <div className="section">
                    <h3>Direct Accounts</h3>
                    <ProjectDirectAccounts projectId={project.id} />
                  </div>

                  <div className="section">
                    <h3>Goals</h3>
                    <ProjectGoals projectId={project.id} />
                  </div>

                  {reportData[project.id] && (
                    <div className="section">
                      <h3>3-Month Report</h3>
                      <pre>{JSON.stringify(reportData[project.id], null, 2)}</pre>
                    </div>
                  )}
                </div>
              )}
            </div>
          ))}
        </div>
      )}
    </div>
  );
}

function ProjectCounters({ projectId }) {
  const [counterList, setCounterList] = useState([]);
  const [newCounter, setNewCounter] = useState({ provider: '', external_id: '', name: '' });

  useEffect(() => {
    loadCounters();
  }, [projectId]);

  const loadCounters = async () => {
    try {
      const result = await counters.list(projectId);
      if (result.success) {
        setCounterList(result.data);
      }
    } catch (error) {
      console.error('Failed to load counters', error);
    }
  };

  const handleCreateCounter = async () => {
    if (!newCounter.provider || !newCounter.external_id) return;

    try {
      const result = await counters.create(projectId, newCounter);
      if (result.success) {
        setCounterList([...counterList, result.data]);
        setNewCounter({ provider: '', external_id: '', name: '' });
      }
    } catch (error) {
      console.error('Failed to create counter', error);
    }
  };

  const handleDeleteCounter = async (counterId) => {
    try {
      const result = await counters.delete(projectId, counterId);
      if (result.success) {
        setCounterList(counterList.filter(c => c.id !== counterId));
      }
    } catch (error) {
      console.error('Failed to delete counter', error);
    }
  };

  return (
    <div className="project-sub-section">
      <div className="add-item-form">
        <input
          type="text"
          placeholder="Provider"
          value={newCounter.provider}
          onChange={(e) => setNewCounter({ ...newCounter, provider: e.target.value })}
        />
        <input
          type="text"
          placeholder="External ID"
          value={newCounter.external_id}
          onChange={(e) => setNewCounter({ ...newCounter, external_id: e.target.value })}
        />
        <input
          type="text"
          placeholder="Name"
          value={newCounter.name}
          onChange={(e) => setNewCounter({ ...newCounter, name: e.target.value })}
        />
        <button onClick={handleCreateCounter}>Add Counter</button>
      </div>

      <ul>
        {counterList.map((counter) => (
          <li key={counter.id}>
            <span>{counter.name || counter.external_id}</span>
            <button onClick={() => handleDeleteCounter(counter.id)}>X</button>
          </li>
        ))}
      </ul>
    </div>
  );
}

function ProjectDirectAccounts({ projectId }) {
  const [accountList, setAccountList] = useState([]);
  const [newAccount, setNewAccount] = useState({ provider: '', external_id: '', name: '' });

  useEffect(() => {
    loadAccounts();
  }, [projectId]);

  const loadAccounts = async () => {
    try {
      const result = await directAccounts.list(projectId);
      if (result.success) {
        setAccountList(result.data);
      }
    } catch (error) {
      console.error('Failed to load direct accounts', error);
    }
  };

  const handleCreateAccount = async () => {
    if (!newAccount.provider || !newAccount.external_id) return;

    try {
      const result = await directAccounts.create(projectId, newAccount);
      if (result.success) {
        setAccountList([...accountList, result.data]);
        setNewAccount({ provider: '', external_id: '', name: '' });
      }
    } catch (error) {
      console.error('Failed to create direct account', error);
    }
  };

  const handleDeleteAccount = async (accountId) => {
    try {
      const result = await directAccounts.delete(projectId, accountId);
      if (result.success) {
        setAccountList(accountList.filter(a => a.id !== accountId));
      }
    } catch (error) {
      console.error('Failed to delete direct account', error);
    }
  };

  return (
    <div className="project-sub-section">
      <div className="add-item-form">
        <input
          type="text"
          placeholder="Provider"
          value={newAccount.provider}
          onChange={(e) => setNewAccount({ ...newAccount, provider: e.target.value })}
        />
        <input
          type="text"
          placeholder="External ID"
          value={newAccount.external_id}
          onChange={(e) => setNewAccount({ ...newAccount, external_id: e.target.value })}
        />
        <input
          type="text"
          placeholder="Name"
          value={newAccount.name}
          onChange={(e) => setNewAccount({ ...newAccount, name: e.target.value })}
        />
        <button onClick={handleCreateAccount}>Add Account</button>
      </div>

      <ul>
        {accountList.map((account) => (
          <li key={account.id}>
            <span>{account.name || account.external_id}</span>
            <button onClick={() => handleDeleteAccount(account.id)}>X</button>
          </li>
        ))}
      </ul>
    </div>
  );
}

function ProjectGoals({ projectId }) {
  const [goalList, setGoalList] = useState([]);
  const [newGoal, setNewGoal] = useState({ name: '', external_id: '' });

  useEffect(() => {
    loadGoals();
  }, [projectId]);

  const loadGoals = async () => {
    try {
      const result = await goals.list(projectId);
      if (result.success) {
        setGoalList(result.data);
      }
    } catch (error) {
      console.error('Failed to load goals', error);
    }
  };

  const handleCreateGoal = async () => {
    if (!newGoal.name) return;

    try {
      const result = await goals.create(projectId, newGoal);
      if (result.success) {
        setGoalList([...goalList, result.data]);
        setNewGoal({ name: '', external_id: '' });
      }
    } catch (error) {
      console.error('Failed to create goal', error);
    }
  };

  const handleDeleteGoal = async (goalId) => {
    try {
      const result = await goals.delete(projectId, goalId);
      if (result.success) {
        setGoalList(goalList.filter(g => g.id !== goalId));
      }
    } catch (error) {
      console.error('Failed to delete goal', error);
    }
  };

  return (
    <div className="project-sub-section">
      <div className="add-item-form">
        <input
          type="text"
          placeholder="Goal name"
          value={newGoal.name}
          onChange={(e) => setNewGoal({ ...newGoal, name: e.target.value })}
        />
        <input
          type="text"
          placeholder="External ID (optional)"
          value={newGoal.external_id}
          onChange={(e) => setNewGoal({ ...newGoal, external_id: e.target.value })}
        />
        <button onClick={handleCreateGoal}>Add Goal</button>
      </div>

      <ul>
        {goalList.map((goal) => (
          <li key={goal.id}>
            <span>{goal.name}</span>
            <button onClick={() => handleDeleteGoal(goal.id)}>X</button>
          </li>
        ))}
      </ul>
    </div>
  );
}
