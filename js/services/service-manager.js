/**
 * Created by Kristopher on 11/25/2016.
 */
angular
    .module('sgdp.service-manager', [])
    .factory('Manager', manager);

manager.$inject = ['$http', '$q', 'Requests'];

function manager($http, $q, Requests) {

    var self = this;

    // Data initialization
    var data = {};
    data.model = {};
    data.model.query = -1;
    data.queries = [
        {category: 'req', name: 'Por cédula', id: 0},
        {category: 'req', name: 'Por estatus', id: 1},
        {category: 'req', name: 'Por tipo', id: 8},
        {category: 'date', name: 'Intervalo de fecha', id: 2},
        {category: 'date', name: 'Fecha exacta', id: 3},
        {category: 'money', name: 'Intervalo de fecha', id: 4},
        {category: 'money', name: 'Por cédula', id: 5},
        {category: 'report', name: 'Intervalo de fecha', id: 6},
        {category: 'report', name: 'Semana actual', id: 7}
    ];
    data.model.perform = new Array(data.queries.length);
    // initialize all ng-model variables.
    for (var i = 0; i < data.queries.length; i++) {
        data.model.perform[i] = {};
    }
    data.selectedQuery = -1;
    data.showOptions = true;
    data.showResult = -1;
    data.chart = null;
    data.pie = null;
    data.pieError = '';
    data.report = null;
    data.fetchId = '';
    data.fetchError = '';
    data.approvalReportError = '';
    data.showPendingReq = false;
    data.showAdvSearch = false;
    data.requests = {};
    data.selectedReq = '';
    data.selectedLoan = -1;
    data.showApprovedAmount = false;
    data.pendingRequests = {};
    data.selectedPendingReq = '';
    data.selectedPendingLoan = -1;
    data.req = {}; // Selected request obj.
    data.showList = Requests.initializeListType();
    data.showPendingList = Requests.initializeListType();
    // End of data initialization.

    self.data = data;

    /**
     * Updates the data.
     *
     * @param data - data to be updated.
     */
    self.updateData = function (data) {
        self.data = data;
    };

    /**
     * Clears the service's data (i.e. re-initializes it)
     */
    self.clearData = function () {
        self.data.model = {};
        self.data.model.query = -1;
        self.data.queries = [
            {category: 'req', name: 'Por cédula', id: 0},
            {category: 'req', name: 'Por estatus', id: 1},
            {category: 'req', name: 'Por tipo', id: 8},
            {category: 'date', name: 'Intervalo de fecha', id: 2},
            {category: 'date', name: 'Fecha exacta', id: 3},
            {category: 'money', name: 'Intervalo de fecha', id: 4},
            {category: 'money', name: 'Por cédula', id: 5},
            {category: 'report', name: 'Intervalo de fecha', id: 6},
            {category: 'report', name: 'Semana actual', id: 7}
        ];
        self.data.model.perform = new Array(data.queries.length);
        // initialize all ng-model variables.
        for (var i = 0; i < self.data.queries.length; i++) {
            self.data.model.perform[i] = {};
        }
        self.data.selectedQuery = -1;
        self.data.showOptions = true;
        self.data.showResult = -1;
        self.data.chart = null;
        self.data.pie = null;
        self.data.pieError = '';
        self.data.report = null;
        self.data.fetchId = '';
        self.data.fetchError = '';
        self.data.approvalReportError = '';
        self.data.showPendingReq = false;
        self.data.showAdvSearch = false;
        self.data.requests = {};
        self.data.selectedReq = '';
        self.data.selectedLoan = -1;
        self.data.showApprovedAmount = false;
        self.data.pendingRequests = {};
        self.data.selectedPendingReq = '';
        self.data.selectedPendingLoan = -1;
        self.data.req = {}; // Selected request obj.
        self.data.showList = Requests.initializeListType();
        self.data.showPendingList = Requests.initializeListType();
    };

    /**
     * Fetches requests the match the specified status.
     *
     * @param status - request status.
     * @returns {*} - promise containing the operation's result.
     */
    self.fetchRequestsByStatus = function (status) {
        var qStatus = $q.defer();
        $http.get('index.php/ManagerHomeController/fetchRequestsByStatus',
            {params: {status: status}})
            .then(
            function (response) {
                console.log(response);
                if (response.data.message === "success") {
                    response.data.requests = Requests.filterRequests(response.data.requests);
                    qStatus.resolve(response.data);
                } else {
                    qStatus.reject(response.data.error);
                }

            });
        return qStatus.promise;
    };

    self.fetchPendingRequests = function () {
        var qPending = $q.defer();
        $http.get('index.php/ManagerHomeController/fetchPendingRequests')
            .then(
            function (response) {
                console.log(response);
                if (response.data.message === "success") {
                    response.data.requests = Requests.filterRequests(response.data.requests);
                    qPending.resolve(response.data);
                } else {
                    qPending.reject(response.data.error);
                }

            });
        return qPending.promise;
    };

    /**
     * Fetches requests the match the specified loan type.
     *
     * @param loanType - loan type code.
     * @returns {*} - promise containing the operation's result.
     */
    self.fetchRequestsByLoanType = function (loanType) {
        var qLoanType = $q.defer();
        $http.get('index.php/ManagerHomeController/fetchRequestsByLoanType',
            {params: {loanType: loanType}})
            .then(
            function (response) {
                if (response.data.message === "success") {
                    response.data.requests = Requests.filterRequests(response.data.requests);
                    qLoanType.resolve(response.data);
                } else {
                    qLoanType.reject(response.data.error);
                }

            });
        return qLoanType.promise;
    };

    /**
     * Gets the user requests and associated data.
     *
     * @param fetchId - Id of the user to fetch the data from.
     * @returns {*} - promise containing the operation's result.
     */
    self.getUserRequests = function (fetchId) {
        var qRequests = $q.defer();
        $http.get('index.php/ManagerHomeController/getUserRequests',
            {params: {fetchId: fetchId}})
            .then(
            function (response) {
                console.log(response);
                if (response.data.message === "success") {
                    response.data.requests = Requests.filterRequests(response.data.requests);
                    qRequests.resolve(response.data);
                } else {
                    qRequests.reject(response.data.error);
                }
            });
        return qRequests.promise;
    };

    /**
     * Gets the requests created within the specified date interval.
     *
     * @param from - date from which to start the look up.
     * @param to - date from which to end the look up.
     * @returns {*} - promise containing the operation's result.
     */
    self.fetchRequestsByDateInterval = function (from, to) {
        var qRequests = $q.defer();
        $http.get('index.php/ManagerHomeController/' +
                  'fetchRequestsByDateInterval',
            {
                params: {
                    from: moment(from).format('DD/MM/YYYY'),
                    to: moment(to).format('DD/MM/YYYY')
                }
            })
            .then(
            function (response) {
                console.log(response);
                if (response.data.message === "success") {
                    response.data.requests = Requests.filterRequests(response.data.requests);
                    qRequests.resolve(response.data);
                } else {
                    qRequests.reject(response.data.error);
                }
            });
        return qRequests.promise;
    };

    /**
     * Gets the requests created within the specified date.
     *
     * @param date - creation date from which to look the requests up.
     * @returns {*} - promise containing the operation's result.
     */
    self.fetchRequestsByExactDate = function (date) {
        var qRequests = $q.defer();
        $http.get('index.php/ManagerHomeController/' +
                  'fetchRequestsByDateInterval',
            {
                params: {
                    from: moment(date).format('DD/MM/YYYY'),
                    to: moment(date).format('DD/MM/YYYY')
                }
            })
            .then(
            function (response) {
                console.log(response);
                if (response.data.message === "success") {
                    response.data.requests = Requests.filterRequests(response.data.requests);
                    qRequests.resolve(response.data);
                } else {
                    qRequests.reject(response.data.error);
                }
            });
        return qRequests.promise;
    };


    /**
     * Gets the total approved amount within a specified date interval.
     *
     * @param from - date from which to start the look up.
     * @param to - date from which to end the look up.
     * @returns {*} - promise containing the operation's result.
     */
    self.getApprovedAmountByDateInterval = function (from, to) {
        var qAmount = $q.defer();
        $http.get('index.php/ManagerHomeController/' +
                  'getApprovedAmountByDateInterval',
            {
                params: {
                    from: moment(from).format('DD/MM/YYYY'),
                    to: moment(to).format('DD/MM/YYYY')
                }
            })
            .then(
            function (response) {
                console.log(response);
                if (response.data.message === "success") {
                    qAmount.resolve(response.data.approvedAmount);
                } else {
                    qAmount.reject(response.data.error);
                }
            });
        return qAmount.promise;
    };

    /**
     * Gets the total approved amount from a specified user.
     *
     * @param userId - corresponding user's id.
     * @returns {*} - promise containing the operation's result.
     */
    self.getApprovedAmountById = function (userId) {
        var qAmount = $q.defer();
        $http.get('index.php/ManagerHomeController/getApprovedAmountById',
            {params: {userId: userId}})
            .then(
            function (response) {
                console.log(response);
                if (response.data.message === "success") {
                    qAmount.resolve(response.data);
                } else {
                    qAmount.reject(response.data.error);
                }
            });
        return qAmount.promise;
    };

    /**
     * Gets the closed requests report within the current week.
     *
     * @returns {*} - promise containing the operation's result.
     */
    self.getClosedReportByCurrentWeek = function () {
        var qReport = $q.defer();
        $http.get('index.php/ManagerHomeController/' +
                  'getClosedReportByCurrentWeek')
            .then(
            function (response) {
                console.log(response);
                if (response.data.message === "success") {
                    qReport.resolve(response.data.report);
                } else {
                    qReport.reject(response.data.error);
                }
            });
        return qReport.promise;
    };

    /**
     * Gets the closed requests report within the specified date interval.
     *
     * @param from - date from which to start the look up.
     * @param to - date from which to end the look up.
     * @returns {*} - promise containing the operation's result.
     */
    self.getClosedReportByDateInterval = function (from, to) {
        var qReport = $q.defer();
        $http.get('index.php/ManagerHomeController/' +
                  'getClosedReportByDateInterval',
            {
                params: {
                    from: moment(from).format('DD/MM/YYYY'),
                    to: moment(to).format('DD/MM/YYYY')
                }
            })
            .then(
            function (response) {
                console.log(response);
                if (response.data.message === "success") {
                    qReport.resolve(response.data.report);
                } else {
                    qReport.reject(response.data.error);
                }
            });
        return qReport.promise;
    };

    /**
     * Updates the specified request.
     *
     * @param request - request with updated information.
     * @returns {*} - promise containing the operation's result.
     */
    self.updateRequest = function (request) {
        var qUpdate = $q.defer();

        $http.post('index.php/ManageRequestController/updateRequest', JSON.stringify(request))
            .then(
            function (response) {
                if (response.status == 200) {
                    qUpdate.resolve();
                } else {
                    qUpdate.reject('Ha ocurrido un error al actualizar la solicitud. Por favor ' +
                                   'intente más tarde.');
                }
            });
        return qUpdate.promise;
    };

    /**
     * Creates a new agent user.
     *
     * @param userData - the new agent user's data.
     * @returns {*} - promise containing the operation's result.
     */
    self.createNewAgent = function (userData) {
        var qAgent = $q.defer();
        $http.post('index.php/ManageAgentUsers/createNewAgent', userData)
            .then(
            function (response) {
                if (response.data.message == "success") {
                    qAgent.resolve();
                } else {
                    qAgent.reject(response.data.message);
                }
            });
        return qAgent.promise;
    };

    /**
     * Fetches all agent users and puts them in a value/display list.
     *
     * @returns {*} - promise containing the operation's result.
     */
    self.fetchAllAgents = function () {
        var qAgents = $q.defer();
        $http.get('index.php/ManageAgentUsers/fetchAllAgents')
            .then(
            function (response) {
                if (response.status == 200) {
                    var allAgents = response.data.agents;
                    var agentsList = allAgents.map(function (agent) {
                        return {
                            value: agent.split('(')[0].trim(),
                            display: agent
                        };
                    });
                    qAgents.resolve(agentsList);
                } else {
                    qAgents.reject('Ha ocurrido un error en el sistema. Por favor intente más tarde.');
                }
            });
        return qAgents.promise;
    };

    /**
     * Deletes (actually, disables) the specified Agent User from the system.
     *
     * @param userId - user to delete's ID.
     * @returns {*} - promise containing the operation's result.
     */
    self.deleteAgentUser = function (userId) {
        var qAgent = $q.defer();
        $http.post('index.php/ManageAgentUsers/deleteAgentUser', userId)
            .then(
            function (response) {
                if (response.status == 200) {
                    qAgent.resolve();
                } else {
                    qAgent.reject(response.data.message);
                }
            });
        return qAgent.promise;
    };

    /**
     * Generates an excel report based on statistical data.
     *
     * @param type - type of the report.
     * @param reportData - statistical data used in constructing the excel report.
     * @returns {*} - promise containing the operation's result.
     */
    self.generateExcelReport = function (type, reportData) {
        var qReport = $q.defer();
        var url = '';
        if (type == 0 || type == 8) {
            reportData.sheetTitle = type == 0 ? "Reporte de afiliado" : "Reporte por tipo";
            url = 'index.php/DocumentGenerator/generateSimpleRequestsReport';
        } else if (type == 2 || type == 3) {
            reportData.sheetTitle = reportData.sheetTitle = "Reporte por fechas";
            url = 'index.php/DocumentGenerator/generateRequestsReport';
        } else if (type == 1) {
            url = 'index.php/DocumentGenerator/generateStatusRequestsReport';
        } else {
            // Approved requests report
            url = 'index.php/DocumentGenerator/generateClosedRequestsReport';
        }
        var report = JSON.stringify(reportData);
        $http.post(url, report).then(function (response) {
            console.log(response);
            if (response.data.message == "success") {
                qReport.resolve('index.php/DocumentGenerator/' +
                                'downloadReport?lpath=' + response.data.lpath);
            } else {
                qReport.reject('Ha ocurrido un error al intentar generar el reporte. ' +
                               'Por favor intente más tarde.');
            }
        });
        return qReport.promise;
    };

    return self;
}
